<?php

namespace Laravel\Passport\Guards;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportUserProvider;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\TransientToken;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

class TokenGuard implements Guard
{
    use GuardHelpers, Macroable;

    /**
     * The resource server instance.
     *
     * @var \League\OAuth2\Server\ResourceServer
     */
    protected $server;

    /**
     * The user provider implementation.
     *
     * @var \Laravel\Passport\PassportUserProvider
     */
    protected $provider;

    /**
     * The token repository instance.
     *
     * @var \Laravel\Passport\TokenRepository
     */
    protected $tokens;

    /**
     * The client repository instance.
     *
     * @var \Laravel\Passport\ClientRepository
     */
    protected $clients;

    /**
     * The encrypter implementation.
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The currently authenticated client.
     *
     * @var \Laravel\Passport\Client|null
     */
    protected $client;

    /**
     * Create a new token guard instance.
     *
     * @param  \League\OAuth2\Server\ResourceServer  $server
     * @param  \Laravel\Passport\PassportUserProvider  $provider
     * @param  \Laravel\Passport\TokenRepository  $tokens
     * @param  \Laravel\Passport\ClientRepository  $clients
     * @param  \Illuminate\Contracts\Encryption\Encrypter  $encrypter
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(
        ResourceServer $server,
        PassportUserProvider $provider,
        TokenRepository $tokens,
        ClientRepository $clients,
        Encrypter $encrypter,
        Request $request
    ) {
        $this->server = $server;
        $this->tokens = $tokens;
        $this->clients = $clients;
        $this->provider = $provider;
        $this->encrypter = $encrypter;
        $this->request = $request;
    }

    /**
     * Get the user for the incoming request.
     *
     * @return mixed
     */
    public function user()
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        if ($this->request->bearerToken()) {
            return $this->user = $this->authenticateViaBearerToken($this->request);
        } elseif ($this->request->cookie(Passport::cookie())) {
            return $this->user = $this->authenticateViaCookie($this->request);
        }
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        return ! is_null((new static(
            $this->server,
            $this->provider,
            $this->tokens,
            $this->clients,
            $this->encrypter,
            $credentials['request'],
        ))->user());
    }

    /**
     * Get the client for the incoming request.
     *
     * @return mixed
     */
    public function client()
    {
        if (! is_null($this->client)) {
            return $this->client;
        }

        if ($this->request->bearerToken()) {
            if (! $psr = $this->getPsrRequestViaBearerToken($this->request)) {
                return;
            }

            return $this->client = $this->clients->findActive(
                $psr->getAttribute('oauth_client_id')
            );
        } elseif ($this->request->cookie(Passport::cookie())) {
            if ($token = $this->getTokenViaCookie($this->request)) {
                return $this->client = $this->clients->findActive($token['aud']);
            }
        }
    }

    /**
     * Authenticate the incoming request via the Bearer token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function authenticateViaBearerToken($request)
    {
        if (! $psr = $this->getPsrRequestViaBearerToken($request)) {
            return;
        }

        $client = $this->clients->findActive(
            $psr->getAttribute('oauth_client_id')
        );

        if (! $client ||
            ($client->provider &&
             $client->provider !== $this->provider->getProviderName())) {
            return;
        }

        // If the access token is valid we will retrieve the user according to the user ID
        // associated with the token. We will use the provider implementation which may
        // be used to retrieve users from Eloquent. Next, we'll be ready to continue.
        $user = $this->provider->retrieveById(
            $psr->getAttribute('oauth_user_id') ?: null
        );

        if (! $user) {
            return;
        }

        // Next, we will assign a token instance to this user which the developers may use
        // to determine if the token has a given scope, etc. This will be useful during
        // authorization such as within the developer's Laravel model policy classes.
        $token = $this->tokens->find(
            $psr->getAttribute('oauth_access_token_id')
        );

        return $token ? $user->withAccessToken($token) : null;
    }

    /**
     * Authenticate and get the incoming PSR-7 request via the Bearer token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    protected function getPsrRequestViaBearerToken($request)
    {
        // First, we will convert the Symfony request to a PSR-7 implementation which will
        // be compatible with the base OAuth2 library. The Symfony bridge can perform a
        // conversion for us to a new Nyholm implementation of this PSR-7 request.
        $psr = (new PsrHttpFactory(
            new Psr17Factory,
            new Psr17Factory,
            new Psr17Factory,
            new Psr17Factory
        ))->createRequest($request);

        try {
            return $this->server->validateAuthenticatedRequest($psr);
        } catch (OAuthServerException $e) {
            $request->headers->set('Authorization', '', true);

            Container::getInstance()->make(
                ExceptionHandler::class
            )->report($e);
        }
    }

    /**
     * Authenticate the incoming request via the token cookie.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function authenticateViaCookie($request)
    {
        if (! $token = $this->getTokenViaCookie($request)) {
            return;
        }

        // If this user exists, we will return this user and attach a "transient" token to
        // the user model. The transient token assumes it has all scopes since the user
        // is physically logged into the application via the application's interface.
        if ($user = $this->provider->retrieveById($token['sub'])) {
            return $user->withAccessToken(new TransientToken);
        }
    }

    /**
     * Get the token cookie via the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function getTokenViaCookie($request)
    {
        // If we need to retrieve the token from the cookie, it'll be encrypted so we must
        // first decrypt the cookie and then attempt to find the token value within the
        // database. If we can't decrypt the value we'll bail out with a null return.
        try {
            $token = $this->decodeJwtTokenCookie($request);
        } catch (Exception $e) {
            return;
        }

        // We will compare the CSRF token in the decoded API token against the CSRF header
        // sent with the request. If they don't match then this request isn't sent from
        // a valid source and we won't authenticate the request for further handling.
        if (! Passport::$ignoreCsrfToken && (! $this->validCsrf($token, $request) ||
            time() >= $token['expiry'])) {
            return;
        }

        return $token;
    }

    /**
     * Decode and decrypt the JWT token cookie.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function decodeJwtTokenCookie($request)
    {
        return (array) JWT::decode(
            CookieValuePrefix::remove($this->encrypter->decrypt($request->cookie(Passport::cookie()), Passport::$unserializesCookies)),
            new Key(Passport::tokenEncryptionKey($this->encrypter), 'HS256')
        );
    }

    /**
     * Determine if the CSRF / header are valid and match.
     *
     * @param  array  $token
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function validCsrf($token, $request)
    {
        return isset($token['csrf']) && hash_equals(
            $token['csrf'], (string) $this->getTokenFromRequest($request)
        );
    }

    /**
     * Get the CSRF token from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function getTokenFromRequest($request)
    {
        $token = $request->header('X-CSRF-TOKEN');

        if (! $token && $header = $request->header('X-XSRF-TOKEN')) {
            $token = CookieValuePrefix::remove($this->encrypter->decrypt($header, static::serialized()));
        }

        return $token;
    }

    /**
     * Set the current request instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Determine if the cookie contents should be serialized.
     *
     * @return bool
     */
    public static function serialized()
    {
        return EncryptCookies::serialized('XSRF-TOKEN');
    }

    /**
     * Set the client for the current request.
     *
     * @param  \Laravel\Passport\Client  $client
     * @return $this
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }
}
