<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Passport\Contracts\DeviceAuthorizationResultViewResponse;
use League\OAuth2\Server\AuthorizationServer;

class DenyDeviceAuthorizationController
{
    use HandlesOAuthErrors, RetrievesDeviceCodeFromSession;

    /**
     * Create a new controller instance.
     */
    public function __construct(protected AuthorizationServer $server,
                                protected DeviceAuthorizationResultViewResponse $viewResponse)
    {
    }

    /**
     * Deny the device authorization request.
     */
    public function __invoke(Request $request): DeviceAuthorizationResultViewResponse
    {
        $this->withErrorHandling(fn () => $this->server->completeDeviceAuthorizationRequest(
            $this->getDeviceCodeFromSession($request),
            $request->user()->getAuthIdentifier(),
            false
        ));

        return $this->viewResponse->withParameters([
            'request' => $request,
            'approved' => false,
        ]);
    }
}
