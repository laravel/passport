<?php

namespace Laravel\Passport\Http\Controllers;

use League\OAuth2\Server\TokenServer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Response;

class IntrospectTokenController
{
    use ConvertsPsrResponses, HandlesOAuthErrors;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected TokenServer $server,
    ) {
    }

    /**
     * Introspect a token.
     */
    public function __invoke(ServerRequestInterface $psrRequest, ResponseInterface $psrResponse): Response
    {
        return $this->withErrorHandling(
            fn () => $this->convertResponse(
                $this->server->respondToTokenIntrospectionRequest($psrRequest, $psrResponse)
            )
        );
    }
}
