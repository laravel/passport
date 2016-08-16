<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Http\Request;
use Zend\Diactoros\Response as Psr7Response;
use League\OAuth2\Server\AuthorizationServer;
use Laravel\Passport\Bridge\AccessTokenRepository;
use Illuminate\Routing\Controller;

class ApproveAuthorizationController extends Controller
{
    use HandlesOAuthErrors, RetrievesAuthRequestFromSession;

    /**
     * The authorization server.
     *
     * @var AuthorizationServer
     */
    protected $server;

    /**
     * The token repository implementation.
     *
     * @var AccessTokenRepository
     */
    protected $tokens;

    /**
     * Create a new controller instance.
     *
     * @param  AuthorizationServer  $server
     * @param  AccessTokenRepository  $tokens
     * @return void
     */
    public function __construct(AuthorizationServer $server, AccessTokenRepository $tokens)
    {
        $this->server = $server;
        $this->tokens = $tokens;
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

            $this->tokens->revokeUserAccessTokensForClient(
                $authRequest->getClient()->getIdentifier(),
                $authRequest->getUser()->getIdentifier()
            );

            return $this->server->completeAuthorizationRequest(
                $authRequest, new Psr7Response
            );
        });
    }
}
