<?php

namespace Laravel\Passport;

use Lcobucci\JWT\Parser as JwtParser;
use League\OAuth2\Server\AuthorizationServer;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class PersonalAccessTokenFactory
{
    /**
     * The authorization server instance.
     *
     * @var \League\OAuth2\Server\AuthorizationServer
     */
    protected $server;

    /**
     * The token repository instance.
     *
     * @var \Laravel\Passport\TokenRepository
     */
    protected $tokens;

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
     * @param  \Laravel\Passport\TokenRepository  $tokens
     * @param  \Lcobucci\JWT\Parser  $jwt
     * @return void
     */
    public function __construct(AuthorizationServer $server, TokenRepository $tokens, JwtParser $jwt)
    {
        $this->jwt = $jwt;
        $this->tokens = $tokens;
        $this->server = $server;
    }

    /**
     * Create a new personal access token.
     *
     * @param  mixed  $userId
     * @param  string  $name
     * @param  string[]  $scopes
     * @return \Laravel\Passport\PersonalAccessTokenResult
     */
    public function make($userId, string $name, array $scopes = [])
    {
        $response = $this->dispatchRequestToAuthorizationServer(
            $this->createRequest($userId, $scopes)
        );

        $token = tap($this->findAccessToken($response), function ($token) use ($userId, $name) {
            $this->tokens->save($token->forceFill([
                'user_id' => $userId,
                'name' => $name,
            ]));
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
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    protected function createRequest($userId, array $scopes)
    {
        $config = config('passport.personal_access_client');

        if (! $config) {
            throw new RuntimeException(
                'Personal access client not found. Please create one and set the `PASSPORT_PERSONAL_ACCESS_CLIENT_ID` and `PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET` environment variables.'
            );
        }

        return (new ServerRequest('POST', 'not-important'))->withParsedBody([
            'grant_type' => 'personal_access',
            'client_id' => $config['id'] ?? null,
            'client_secret' => $config['secret'] ?? null,
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
        return $this->tokens->find(
            $this->jwt->parse($response['access_token'])->claims()->get('jti')
        );
    }
}
