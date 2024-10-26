<?php

namespace Laravel\Passport\Http\Controllers;

use League\OAuth2\Server\AuthorizationServer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Response;

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
    public function issueToken(ServerRequestInterface $psrRequest, ResponseInterface $psrResponse): Response
    {
        return $this->withErrorHandling(fn () => $this->convertResponse(
            $this->server->respondToAccessTokenRequest($psrRequest, $psrResponse)
        ));
    }
}
