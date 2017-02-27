<?php

namespace Laravel\Passport\Http\Controllers;

use App;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Bridge\UserRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\TokenRepository;
use Lcobucci\JWT\Parser as JwtParser;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\PasswordGrant;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\AuthorizationServer;

class AccessTokenController
{
    use HandlesOAuthErrors;

    /**
     * The authorization server.
     *
     * @var AuthorizationServer
     */
    protected $server;

    /**
     * The token repository instance.
     *
     * @var TokenRepository
     */
    protected $tokens;

    /**
     * The JWT parser instance.
     *
     * @var JwtParser
     */
    protected $jwt;

    /**
     * Create a new controller instance.
     *
     * @param  AuthorizationServer  $server
     * @param  TokenRepository  $tokens
     * @param  JwtParser  $jwt
     * @return void
     */
    public function __construct(AuthorizationServer $server,
                                TokenRepository $tokens,
                                JwtParser $jwt)
    {
        $this->jwt = $jwt;
        $this->server = $server;
        $this->tokens = $tokens;
    }

    /**
     * Authorize a client to access the user's account.
     *
     * @param  ServerRequestInterface  $request
     * @return Response
     */
    public function issueToken(ServerRequestInterface $request)
    {
        $provider = $this->getProvider($request);

        $this->overwritePasswordGrantUserRepository($request, $provider);

        $response = $this->withErrorHandling(function () use ($request) {
            return $this->server->respondToAccessTokenRequest($request, new Psr7Response);
        });

        $this->storeTokenUserProvider($response, $provider);

        return $response;
    }

    /**
     * This creates a replacement PasswordGrant in the AuthorizationServer which has
     * the correct user provider set in the user repository.
     *
     * While this shits all over the DI, it also avoids having to add provider support
     * in the league/oauth2-server package.
     *
     * @param ServerRequestInterface $request
     * @param string $provider
     */
    protected function overwritePasswordGrantUserRepository(ServerRequestInterface $request, string $provider)
    {
        $grant_type = $request->getParsedBody()['grant_type'] ?? null;

        if($grant_type == 'password'){

            $userRepository = App::make(UserRepository::class);
            $userRepository->setProvider($provider);

            $refreshTokenRepository = App::make(RefreshTokenRepository::class);

            $grant = new PasswordGrant(
                $userRepository,
                $refreshTokenRepository
            );

            $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());

            $this->server->enableGrantType(
                $grant,
                Passport::tokensExpireIn()
            );
        }

    }

    /**
     * This bends the OAuth2 spec slightly, by allowing clients to
     * specify a provider, defaulting to "users"
     *
     * @param ServerRequestInterface $request
     * @return string
     * @throws OAuthServerException
     */
    protected function getProvider(ServerRequestInterface $request)
    {
        $providers = array_keys(config('auth.providers'));
        $provider = isset($request->getParsedBody()['provider']) ? $request->getParsedBody()['provider'] : 'users';

        if(!in_array($provider, $providers)){
            throw OAuthServerException::invalidRequest('provider');
        }

        return $provider;
    }

    /**
     * Extracts the token from the AuthorizationServer response, finds
     * the token in the repository, and sets the correct provider.
     *
     * @param ResponseInterface $response
     * @param $provider
     */
    protected function storeTokenUserProvider(ResponseInterface $response, $provider)
    {
        $body = \GuzzleHttp\json_decode($response->getBody());

        if(isset($body->error)){
            return;
        }

        $jwt = $this->jwt->parse($body->access_token);
        $token = $this->tokens->find($jwt->getHeader('jti'));
        $token->provider = $provider;
        $this->tokens->save($token);
    }
}
