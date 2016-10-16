<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Http\Request;
use Zend\Diactoros\Response as Psr7Response;
use League\OAuth2\Server\AuthorizationServer;

class ApproveAuthorizationController
{
    use HandlesOAuthErrors, RetrievesAuthRequestFromSession;

    /**
     * The authorization server.
     *
     * @var AuthorizationServer
     */
    protected $server;

    /**
     * Create a new controller instance.
     *
     * @param  AuthorizationServer  $server
     * @return void
     */
    public function __construct(AuthorizationServer $server)
    {
        $this->server = $server;
    }

    /**
     * Approve the authorization request.
     *
     * @param  Request  $request
     * @return Response
     */
    public function approve(Request $request)
    {
        return $this->withErrorHandling(function () use ($request) {
            $authRequest = $this->getAuthRequestFromSession($request);

            return $this->server->completeAuthorizationRequest(
                $authRequest, new Psr7Response
            );
        });
    }
}
