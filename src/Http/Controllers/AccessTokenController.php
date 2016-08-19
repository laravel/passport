<?php

namespace Laravel\Passport\Http\Controllers;

use Laravel\Passport\Passport;
use Laravel\Passport\TokenRepository;
use Lcobucci\JWT\Parser as JwtParser;
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
        $response = $this->withErrorHandling(function () use ($request) {
            return $this->server->respondToAccessTokenRequest($request, new Psr7Response);
        });

        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            return $response;
        }

        $payload = json_decode($response->getBody()->__toString(), true);

        if (isset($payload['access_token'])) {
            $this->revokeOtherAccessTokens($payload);
        }

        return $response;
    }

    /**
     * Revoke the user's other access tokens for the client.
     *
     * @param  array  $payload
     * @return void
     */
    protected function revokeOtherAccessTokens(array $payload)
    {
        $token = $this->tokens->find(
            $tokenId = $this->jwt->parse($payload['access_token'])->getClaim('jti')
        );

        $this->tokens->revokeOtherAccessTokens(
            $token->client_id, $token->user_id, $tokenId, Passport::$pruneRevokedTokens
        );
    }
}
