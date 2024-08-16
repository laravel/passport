<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Http\Response;
use League\OAuth2\Server\AuthorizationServer;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;

class DeviceCodeController
{
    use ConvertsPsrResponses, HandlesOAuthErrors;

    /**
     * Create a new controller instance.
     */
    public function __construct(protected AuthorizationServer $server)
    {
    }

    /**
     * Issue a device code for the client.
     */
    public function __invoke(ServerRequestInterface $request): Response
    {
        return $this->withErrorHandling(fn () => $this->convertResponse(
            $this->server->respondToDeviceAuthorizationRequest($request, new Psr7Response)
        ));
    }
}
