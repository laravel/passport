<?php

namespace Laravel\Passport;

use Lcobucci\JWT\Parser as JwtParser;
use League\OAuth2\Server\AuthorizationServer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;

class PersonalAccessTokenFactory
{
    /**
     * Create a new personal access token factory instance.
     */
    public function __construct(
        protected AuthorizationServer $server,
        protected JwtParser $jwt
    ) {
    }

    /**
     * Create a new personal access token.
     *
     * @param  string[]  $scopes
     */
    public function make(string|int $userId, string $name, array $scopes, string $provider): PersonalAccessTokenResult
    {
        $response = $this->dispatchRequestToAuthorizationServer(
            $this->createRequest($userId, $scopes, $provider)
        );

        $token = tap($this->findAccessToken($response), function (Token $token) use ($name) {
            $token->forceFill([
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
     * @param  string[]  $scopes
     */
    protected function createRequest(string|int $userId, array $scopes, string $provider): ServerRequestInterface
    {
        return (new PsrHttpFactory())->createRequest(Request::create('not-important', 'POST', [
            'grant_type' => 'personal_access',
            'provider' => $provider,
            'user_id' => $userId,
            'scope' => implode(' ', $scopes),
        ]));
    }

    /**
     * Dispatch the given request to the authorization server.
     *
     * @return array<string, mixed>
     */
    protected function dispatchRequestToAuthorizationServer(ServerRequestInterface $request): array
    {
        return json_decode($this->server->respondToAccessTokenRequest(
            $request, app(ResponseInterface::class)
        )->getBody()->__toString(), true);
    }

    /**
     * Get the access token instance for the parsed response.
     *
     * @param  array<string, mixed>  $response
     */
    public function findAccessToken(array $response): Token
    {
        return Passport::token()->newQuery()->find(
            $this->jwt->parse($response['access_token'])->claims()->get('jti')
        );
    }
}
