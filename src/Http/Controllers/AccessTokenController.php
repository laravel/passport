<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Http\Response;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;

class AccessTokenController
{
    use ConvertsPsrResponses, HandlesOAuthErrors;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected AuthorizationServer $server
    ) {
    }

    /**
     * Issue an access token.
     */
    public function issueToken(ServerRequestInterface $request): Response
    {
        return $this->withErrorHandling(function () use ($request) {
            if (array_key_exists('grant_type', $attributes = (array) $request->getParsedBody()) &&
                $attributes['grant_type'] === 'personal_access') {
                throw OAuthServerException::unsupportedGrantType();
            }

            return $this->convertResponse(
                $this->server->respondToAccessTokenRequest($request, new Psr7Response)
            );
        });
    }
}
