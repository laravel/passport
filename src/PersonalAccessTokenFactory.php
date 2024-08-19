<?php

namespace Laravel\Passport;

use Lcobucci\JWT\Parser as JwtParser;
use League\OAuth2\Server\AuthorizationServer;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class PersonalAccessTokenFactory
{
    /**
     * The authorization server instance.
     *
     * @var \League\OAuth2\Server\AuthorizationServer
     */
    protected $server;

    /**
     * The JWT token parser instance.
     *
     * @var \Lcobucci\JWT\Parser
     */
    protected $jwt;

    /**
     * Create a new personal access token factory instance.
     *
     * @param  \League\OAuth2\Server\AuthorizationServer  $server
     * @param  \Lcobucci\JWT\Parser  $jwt
     * @return void
     */
    public function __construct(AuthorizationServer $server,
                                JwtParser $jwt)
    {
        $this->jwt = $jwt;
        $this->server = $server;
    }

    /**
     * Create a new personal access token.
     *
     * @param  mixed  $userId
     * @param  string  $name
     * @param  string[]  $scopes
     * @param  string  $provider
     * @return \Laravel\Passport\PersonalAccessTokenResult
     */
    public function make($userId, string $name, array $scopes, string $provider)
    {
        $response = $this->dispatchRequestToAuthorizationServer(
            $this->createRequest($userId, $scopes, $provider)
        );

        $token = tap($this->findAccessToken($response), function ($token) use ($userId, $name) {
            $token->forceFill([
                'user_id' => $userId,
                'name' => $name,
            ])->save();
        });

        return new PersonalAccessTokenResult(
            $response['access_token'], $token
        );
    }

    /**
     * Create a request instance for the given client.
     *
     * @param  mixed  $userId
     * @param  string[]  $scopes
     * @param  string  $provider
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    protected function createRequest($userId, array $scopes, string $provider)
    {
        return (new ServerRequest('POST', 'not-important'))->withParsedBody([
            'grant_type' => 'personal_access',
            'provider' => $provider,
            'user_id' => $userId,
            'scope' => implode(' ', $scopes),
        ]);
    }

    /**
     * Dispatch the given request to the authorization server.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @return array
     */
    protected function dispatchRequestToAuthorizationServer(ServerRequestInterface $request)
    {
        return json_decode($this->server->respondToAccessTokenRequest(
            $request, new Response
        )->getBody()->__toString(), true);
    }

    /**
     * Get the access token instance for the parsed response.
     *
     * @param  array  $response
     * @return \Laravel\Passport\Token
     */
    public function findAccessToken(array $response)
    {
        return Passport::token()->find(
            $this->jwt->parse($response['access_token'])->claims()->get('jti')
        );
    }
}
