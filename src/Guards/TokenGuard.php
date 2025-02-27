<?php

namespace Laravel\Passport\Guards;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportUserProvider;
use Laravel\Passport\TransientToken;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

class TokenGuard implements Guard
{
    use GuardHelpers, Macroable;

    /**
     * The user provider implementation.
     *
     * @var \Laravel\Passport\PassportUserProvider
     */
    protected $provider;

    /**
     * The currently authenticated client.
     */
    protected ?Client $client = null;

    /**
     * Create a new token guard instance.
     */
    public function __construct(
        protected ResourceServer $server,
        PassportUserProvider $provider,
        protected ClientRepository $clients,
        protected Encrypter $encrypter,
        protected Request $request
    ) {
        $this->provider = $provider;
    }

    /**
     * Get the user for the incoming request.
     *
     * @return \Laravel\Passport\Contracts\OAuthenticatable|null
     */
    public function user(): ?Authenticatable
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        if ($this->request->bearerToken()) {
            return $this->user = $this->authenticateViaBearerToken();
        }

        if ($this->request->cookie(Passport::cookie())) {
            return $this->user = $this->authenticateViaCookie();
        }

        return null;
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function validate(array $credentials = []): bool
    {
        return ! is_null((new static(
            $this->server,
            $this->provider,
            $this->clients,
            $this->encrypter,
            $credentials['request'],
        ))->user());
    }

    /**
     * Get the client for the incoming request.
     */
    public function client(): ?Client
    {
        if (! is_null($this->client)) {
            return $this->client;
        }

        if ($this->request->bearerToken()) {
            if (! $psr = $this->getPsrRequestViaBearerToken()) {
                return null;
            }

            return $this->client = $this->clients->findActive(
                $psr->getAttribute('oauth_client_id')
            );
        }

        if ($this->request->cookie(Passport::cookie()) && $token = $this->getTokenViaCookie()) {
            return $this->client = $this->clients->findActive($token['aud']);
        }

        return null;
    }

    /**
     * Authenticate the incoming request via the Bearer token.
     *
     * @return \Laravel\Passport\Contracts\OAuthenticatable|null
     */
    protected function authenticateViaBearerToken(): ?Authenticatable
    {
        if (! $psr = $this->getPsrRequestViaBearerToken()) {
            return null;
        }

        $client = $this->clients->findActive(
            $psr->getAttribute('oauth_client_id')
        );

        if (! $client ||
            ($client->provider &&
             $client->provider !== $this->provider->getProviderName())) {
            return null;
        }

        $this->setClient($client);

        // If the access token is valid we will retrieve the user according to the user ID
        // associated with the token. We will use the provider implementation which may
        // be used to retrieve users from Eloquent. Next, we'll be ready to continue.
        $user = $this->provider->retrieveById(
            $psr->getAttribute('oauth_user_id') ?: null
        );

        if (! $user) {
            return null;
        }

        // Next, we will assign a token instance to this user which the developers may use
        // to determine if the token has a given scope, etc. This will be useful during
        // authorization such as within the developer's Laravel model policy classes.
        return $user->withAccessToken(AccessToken::fromPsrRequest($psr));
    }

    /**
     * Authenticate and get the incoming PSR-7 request via the Bearer token.
     */
    protected function getPsrRequestViaBearerToken(): ?ServerRequestInterface
    {
        // First, we will convert the Symfony request to a PSR-7 implementation which will
        // be compatible with the base OAuth2 library. The Symfony bridge can perform a
        // conversion for us to a new PSR-7 implementation from this Symfony request.
        $psr = (new PsrHttpFactory)->createRequest($this->request);

        try {
            return $this->server->validateAuthenticatedRequest($psr);
        } catch (OAuthServerException $e) {
            $this->request->headers->set('Authorization', '', true);

            report($e);

            return null;
        }
    }

    /**
     * Authenticate the incoming request via the token cookie.
     *
     * @return \Laravel\Passport\Contracts\OAuthenticatable|null
     */
    protected function authenticateViaCookie(): ?Authenticatable
    {
        if (! $token = $this->getTokenViaCookie()) {
            return null;
        }

        // If this user exists, we will return this user and attach a "transient" token to
        // the user model. The transient token assumes it has all scopes since the user
        // is physically logged into the application via the application's interface.
        if ($user = $this->provider->retrieveById($token['sub'])) {
            return $user->withAccessToken(new TransientToken);
        }

        return null;
    }

    /**
     * Get the token cookie via the incoming request.
     *
     * @return array<string, mixed>|null
     */
    protected function getTokenViaCookie(): ?array
    {
        // If we need to retrieve the token from the cookie, it'll be encrypted so we must
        // first decrypt the cookie and then attempt to find the token value within the
        // database. If we can't decrypt the value we'll bail out with a null return.
        try {
            $token = $this->decodeJwtTokenCookie();
        } catch (Exception) {
            return null;
        }

        // Token's expiration time is checked using the "exp" claim during decoding, but
        // legacy tokens may have an "expiry" claim instead of the standard "exp". So
        // we must manually check token's expiry, if the "expiry" claim is present.
        if (isset($token['expiry']) && time() >= $token['expiry']) {
            return null;
        }

        // We will compare the CSRF token in the decoded API token against the CSRF header
        // sent with the request. If they don't match then this request isn't sent from
        // a valid source and we won't authenticate the request for further handling.
        if (! Passport::$ignoreCsrfToken && ! $this->validCsrf($token)) {
            return null;
        }

        return $token;
    }

    /**
     * Decode and decrypt the JWT token cookie.
     *
     * @return array<string, mixed>
     */
    protected function decodeJwtTokenCookie(): array
    {
        $jwt = $this->request->cookie(Passport::cookie());

        return (array) JWT::decode(
            Passport::$decryptsCookies
                ? CookieValuePrefix::remove($this->encrypter->decrypt($jwt, Passport::$unserializesCookies))
                : $jwt,
            new Key(Passport::tokenEncryptionKey($this->encrypter), 'HS256')
        );
    }

    /**
     * Determine if the CSRF / header are valid and match.
     *
     * @param  array<string, mixed>  $token
     */
    protected function validCsrf(array $token): bool
    {
        return isset($token['csrf']) && hash_equals(
            $token['csrf'], $this->getTokenFromRequest()
        );
    }

    /**
     * Get the CSRF token from the request.
     */
    protected function getTokenFromRequest(): string
    {
        $token = $this->request->header('X-CSRF-TOKEN');

        if (! $token && $header = $this->request->header('X-XSRF-TOKEN')) {
            $token = CookieValuePrefix::remove($this->encrypter->decrypt($header, static::serialized()));
        }

        return $token;
    }

    /**
     * Set the current request instance.
     */
    public function setRequest(Request $request): static
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Determine if the cookie contents should be serialized.
     */
    public static function serialized(): bool
    {
        return EncryptCookies::serialized('XSRF-TOKEN');
    }

    /**
     * Set the client for the current request.
     */
    public function setClient(Client $client): static
    {
        $this->client = $client;

        return $this;
    }
}
