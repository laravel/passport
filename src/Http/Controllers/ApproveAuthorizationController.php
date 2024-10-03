<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use League\OAuth2\Server\AuthorizationServer;
use Nyholm\Psr7\Response as Psr7Response;

class ApproveAuthorizationController
{
    use ConvertsPsrResponses, HandlesOAuthErrors, RetrievesAuthRequestFromSession;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected AuthorizationServer $server
    ) {
    }

    /**
     * Approve the authorization request.
     */
    public function approve(Request $request): Response
    {
        $authRequest = $this->getAuthRequestFromSession($request);

        $authRequest->setAuthorizationApproved(true);

        return $this->withErrorHandling(fn () => $this->convertResponse(
            $this->server->completeAuthorizationRequest($authRequest, new Psr7Response)
        ), $authRequest->getGrantTypeId() === 'implicit');
    }
}
