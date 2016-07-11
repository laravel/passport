<?php

namespace Laravel\Passport\Http\Controllers;

use Laravel\Passport\Passport;
use Illuminate\Http\Request;
use Laravel\Passport\ClientRepository;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\AuthorizationServer;
use Illuminate\Contracts\Routing\ResponseFactory;

class AuthorizationController
{
    use HandlesOAuthErrors;

    /**
     * The authorization server.
     *
     * @var AuthorizationServer
     */
    protected $server;

    /**
     * The response factory implementation.
     *
     * @var ResponseFactory
     */
    protected $response;

    /**
     * Create a new controller instance.
     *
     * @param  AuthorizationServer  $server
     * @param  ResponseFactory  $response
     * @return void
     */
    public function __construct(AuthorizationServer $server, ResponseFactory $response)
    {
        $this->server = $server;
        $this->response = $response;
    }

    /**
     * Authorize a client to access the user's account.
     *
     * @param  ServerRequestInterface  $psrRequest
     * @param  Request  $request
     * @param  ClientRepository  $clients
     * @return Response
     */
    public function authorize(ServerRequestInterface $psrRequest,
                              Request $request,
                              ClientRepository $clients)
    {
        return $this->withErrorHandling(function () use ($psrRequest, $request, $clients) {
            $request->session()->put(
                'authRequest', $authRequest = $this->server->validateAuthorizationRequest($psrRequest)
            );

            $scopes = $this->parseScopes($authRequest);

            return $this->response->view('passport::authorize', [
                'client' => $clients->find($authRequest->getClient()->getIdentifier()),
                'user' => $request->user(),
                'scopes' => $scopes,
                'request' => $request,
            ]);
        });
    }

    /**
     * Transform the authorization requests's scopes into Scope instances.
     *
     * @param  AuthRequest  $request
     * @return array
     */
    protected function parseScopes($authRequest)
    {
        return Passport::scopesFor(
            collect($authRequest->getScopes())->map(function ($scope) {
                return $scope->getIdentifier();
            })->all()
        );
    }
}
