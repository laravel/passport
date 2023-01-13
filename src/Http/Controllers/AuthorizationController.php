<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Passport\Bridge\User;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;

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
     * The guard implementation.
     *
     * @var \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected $guard;

    /**
     * Create a new controller instance.
     *
     * @param  \League\OAuth2\Server\AuthorizationServer  $server
     * @param  \Illuminate\Contracts\Routing\ResponseFactory  $response
     * @param  \Illuminate\Contracts\Auth\StatefulGuard  $guard
     * @return void
     */
    public function __construct(AuthorizationServer $server,
                                ResponseFactory $response,
                                StatefulGuard $guard)
    {
        $this->server = $server;
        $this->response = $response;
        $this->guard = $guard;
    }

    /**
     * Authorize a client to access the user's account.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $psrRequest
     * @param  \Illuminate\Http\Request  $request
     * @param  \Laravel\Passport\ClientRepository  $clients
     * @param  \Laravel\Passport\TokenRepository  $tokens
     * @return \Illuminate\Http\Response
     */
    public function authorize(ServerRequestInterface $psrRequest,
                              Request $request,
                              ClientRepository $clients,
                              TokenRepository $tokens)
    {
        $authRequest = $this->withErrorHandling(function () use ($psrRequest) {
            return $this->server->validateAuthorizationRequest($psrRequest);
        });

        if ($this->guard->guest()) {
            return $request->get('prompt') === 'none'
                    ? $this->denyRequest($authRequest)
                    : $this->promptForLogin($request);
        }

        if ($request->get('prompt') === 'login' &&
            ! $request->session()->get('promptedForLogin', false)) {
            $this->guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->promptForLogin($request);
        }

        $request->session()->forget('promptedForLogin');

        $scopes = $this->parseScopes($authRequest);
        $user = $this->guard->user();
        $client = $clients->find($authRequest->getClient()->getIdentifier());

        if ($request->get('prompt') !== 'consent' &&
            ($client->skipsAuthorization() || $this->hasValidToken($tokens, $user, $client, $scopes))) {
            return $this->approveRequest($authRequest, $user);
        }

        if ($request->get('prompt') === 'none') {
            return $this->denyRequest($authRequest, $user);
        }

        $request->session()->put('authToken', $authToken = Str::random());
        $request->session()->put('authRequest', $authRequest);

        return $this->response->view('passport::authorize', [
            'client' => $client,
            'user' => $user,
            'scopes' => $scopes,
            'request' => $request,
            'authToken' => $authToken,
        ]);
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
            })->unique()->all()
        );
    }

    /**
     * Determine if a valid token exists for the given user, client, and scopes.
     *
     * @param  \Laravel\Passport\TokenRepository  $tokens
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \Laravel\Passport\Client  $client
     * @param  array  $scopes
     * @return bool
     */
    protected function hasValidToken($tokens, $user, $client, $scopes)
    {
        $token = $tokens->findValidToken($user, $client);

        return $token && $token->scopes === collect($scopes)->pluck('id')->all();
    }

    /**
     * Approve the authorization request.
     *
     * @param  \League\OAuth2\Server\RequestTypes\AuthorizationRequest  $authRequest
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return \Illuminate\Http\Response
     */
    protected function approveRequest($authRequest, $user)
    {
        $authRequest->setUser(new User($user->getAuthIdentifier()));

        $authRequest->setAuthorizationApproved(true);

        return $this->withErrorHandling(function () use ($authRequest) {
            return $this->convertResponse(
                $this->server->completeAuthorizationRequest($authRequest, new Psr7Response)
            );
        });
    }

    /**
     * Deny the authorization request.
     *
     * @param  \League\OAuth2\Server\RequestTypes\AuthorizationRequest  $authRequest
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @return \Illuminate\Http\Response
     */
    protected function denyRequest($authRequest, $user = null)
    {
        if (is_null($user)) {
            $uri = $authRequest->getRedirectUri()
                ?? (is_array($authRequest->getClient()->getRedirectUri())
                    ? $authRequest->getClient()->getRedirectUri()[0]
                    : $authRequest->getClient()->getRedirectUri());

            $separator = $authRequest->getGrantTypeId() === 'implicit' ? '#' : '?';

            $uri = $uri.(str_contains($uri, $separator) ? '&' : $separator).'state='.$authRequest->getState();

            return $this->withErrorHandling(function () use ($uri) {
                throw OAuthServerException::accessDenied('Unauthenticated', $uri);
            });
        }

        $authRequest->setUser(new User($user->getAuthIdentifier()));

        $authRequest->setAuthorizationApproved(false);

        return $this->withErrorHandling(function () use ($authRequest) {
            return $this->convertResponse(
                $this->server->completeAuthorizationRequest($authRequest, new Psr7Response)
            );
        });
    }

    /**
     * Prompt the user to login by throwing an AuthenticationException.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function promptForLogin($request)
    {
        $request->session()->put('promptedForLogin', true);

        throw new AuthenticationException;
    }
}
