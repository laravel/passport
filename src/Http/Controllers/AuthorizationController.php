<?php

namespace ROMaster2\Passport\Http\Controllers;

use Illuminate\Http\Request;
use ROMaster2\Passport\Passport;
use ROMaster2\Passport\Bridge\User;
use ROMaster2\Passport\TokenRepository;
use ROMaster2\Passport\ClientRepository;
use Illuminate\Database\Eloquent\Model;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response as Psr7Response;
use League\OAuth2\Server\AuthorizationServer;
use Illuminate\Contracts\Routing\ResponseFactory;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;

class AuthorizationController
{
    use HandlesOAuthErrors;

    /**
     * The authorization server.
     *
     * @var \League\OAuth2\Server\AuthorizationServer
     */
    protected $server;

    /**
     * The response factory implementation.
     *
     * @var \Illuminate\Contracts\Routing\ResponseFactory
     */
    protected $response;

    /**
     * Create a new controller instance.
     *
     * @param  \League\OAuth2\Server\AuthorizationServer  $server
     * @param  \Illuminate\Contracts\Routing\ResponseFactory  $response
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
     * @param  \Psr\Http\Message\ServerRequestInterface  $psrRequest
     * @param  \Illuminate\Http\Request  $request
     * @param  \ROMaster2\Passport\ClientRepository  $clients
     * @param  \ROMaster2\Passport\TokenRepository  $tokens
     * @return \Illuminate\Http\Response
     */
    public function authorize(ServerRequestInterface $psrRequest,
                              Request $request,
                              ClientRepository $clients,
                              TokenRepository $tokens)
    {
        return $this->withErrorHandling(function () use ($psrRequest, $request, $clients, $tokens) {
            $authRequest = $this->server->validateAuthorizationRequest($psrRequest);

            $scopes = $this->parseScopes($authRequest);

            $token = $tokens->findValidToken(
                $user = $request->user(),
                $client = $clients->find($authRequest->getClient()->getIdentifier())
            );

            if ($token && $token->scopes === collect($scopes)->pluck('id')->all()) {
                return $this->approveRequest($authRequest, $user);
            }

            $request->session()->put('authRequest', $authRequest);

            return $this->response->view('passport::authorize', [
                'client' => $client,
                'user' => $user,
                'scopes' => $scopes,
                'request' => $request,
            ]);
        });
    }

    /**
     * Transform the authorization requests's scopes into Scope instances.
     *
     * @param  \League\OAuth2\Server\RequestTypes\AuthorizationRequest  $authRequest
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

    /**
     * Approve the authorization request.
     *
     * @param  \League\OAuth2\Server\RequestTypes\AuthorizationRequest  $authRequest
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return \Illuminate\Http\Response
     */
    protected function approveRequest($authRequest, $user)
    {
        $authRequest->setUser(new User($user->getKey()));

        $authRequest->setAuthorizationApproved(true);

        return $this->convertResponse(
            $this->server->completeAuthorizationRequest($authRequest, new Psr7Response)
        );
    }
}
