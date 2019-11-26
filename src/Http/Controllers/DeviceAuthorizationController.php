<?php

namespace Laravel\Passport\Http\Controllers;

use League\OAuth2\Server\AuthorizationServer;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response as Psr7Response;

class DeviceAuthorizationController
{
    use HandlesOAuthErrors;

    /**
     * The authorization server.
     *
     * @var \League\OAuth2\Server\AuthorizationServer
     */
    protected $server;

    /**
     * Create a new controller instance.
     *
     * @param  \League\OAuth2\Server\AuthorizationServer  $server
     * @return void
     */
    public function __construct(AuthorizationServer $server)
    {
        $this->server = $server;
    }

    /**
     * Authorize a client to access the user's account.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $psrRequest
     * @return \Illuminate\Http\Response
     */
    public function authorize(ServerRequestInterface $psrRequest)
    {
        $deviceAuthRequest = $this->withErrorHandling(function () use ($psrRequest) {
            return $this->server->validateDeviceAuthorizationRequest($psrRequest);
        });

        return $this->convertResponse(
            $this->server->completeDeviceAuthorizationRequest($deviceAuthRequest, new Psr7Response)
        );
    }
}
