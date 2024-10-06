<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Passport\Contracts\DeniedDeviceAuthorizationResponse;
use League\OAuth2\Server\AuthorizationServer;

class DenyDeviceAuthorizationController
{
    use HandlesOAuthErrors, RetrievesDeviceCodeFromSession;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected AuthorizationServer $server
    ) {
    }

    /**
     * Deny the device authorization request.
     */
    public function __invoke(
        Request $request,
        DeniedDeviceAuthorizationResponse $response
    ): DeniedDeviceAuthorizationResponse {
        $this->withErrorHandling(fn () => $this->server->completeDeviceAuthorizationRequest(
            $this->getDeviceCodeFromSession($request),
            $request->user()->getAuthIdentifier(),
            false
        ));

        return $response;
    }
}
