<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Passport\Contracts\DeviceAuthorizationResultViewResponse;
use Laravel\Passport\Contracts\DeviceAuthorizationViewResponse;
use Laravel\Passport\Contracts\DeviceUserCodeViewResponse;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;

class DeviceAuthorizationController
{
    use ConvertsPsrResponses, HandlesOAuthErrors, RetrievesDeviceCodeFromSession;

    /**
     * The authorization server.
     *
     * @var \League\OAuth2\Server\AuthorizationServer
     */
    protected $server;

    /**
     * The user code view response implementation.
     *
     * @var \Laravel\Passport\Contracts\DeviceUserCodeViewResponse
     */
    protected $deviceUserCodeViewResponse;

    /**
     * The authorization view response implementation.
     *
     * @var \Laravel\Passport\Contracts\DeviceAuthorizationViewResponse
     */
    protected $deviceAuthorizationViewResponse;

    /**
     * The authorization result view response implementation.
     *
     * @var \Laravel\Passport\Contracts\DeviceAuthorizationResultViewResponse
     */
    protected $deviceAuthorizationResultViewResponse;

    /**
     * Create a new controller instance.
     *
     * @param  \League\OAuth2\Server\AuthorizationServer  $server
     * @param  \Laravel\Passport\Contracts\DeviceUserCodeViewResponse  $deviceUserCodeViewResponse
     * @param  \Laravel\Passport\Contracts\DeviceAuthorizationViewResponse  $deviceAuthorizationViewResponse
     * @param  \Laravel\Passport\Contracts\DeviceAuthorizationResultViewResponse  $deviceAuthorizationResultViewResponse
     * @return void
     */
    public function __construct(AuthorizationServer $server,
                                DeviceUserCodeViewResponse $deviceUserCodeViewResponse,
                                DeviceAuthorizationViewResponse $deviceAuthorizationViewResponse,
                                DeviceAuthorizationResultViewResponse $deviceAuthorizationResultViewResponse)
    {
        $this->server = $server;
        $this->deviceUserCodeViewResponse = $deviceUserCodeViewResponse;
        $this->deviceAuthorizationViewResponse = $deviceAuthorizationViewResponse;
        $this->deviceAuthorizationResultViewResponse = $deviceAuthorizationResultViewResponse;
    }

    /**
     * Show the form for entering the user code.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Laravel\Passport\Contracts\DeviceUserCodeViewResponse
     */
    public function userCode(Request $request)
    {
        if ($userCode = $request->query('user_code')) {
            return to_route('passport.device.authorizations.authorize', [
                'user_code' => $userCode,
            ]);
        }

        return $this->deviceUserCodeViewResponse->withParameters([
            'request' => $request,
        ]);
    }

    /**
     * Authorize a device to access the user's account.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Laravel\Passport\Contracts\DeviceAuthorizationViewResponse
     */
    public function authorize(Request $request)
    {
        if (! $userCode = $request->query('user_code')) {
            return to_route('passport.device');
        }

        $deviceCode = Passport::deviceCode()
            ->with('client')
            ->where('user_code', $userCode)
            ->where('expires_at', '>', now())
            ->where('revoked', false)
            ->first();

        if (! $deviceCode) {
            return to_route('passport.device')
                ->withInput(['user_code' => $userCode])
                ->withErrors([
                    'user_code' => 'Incorrect code.',
                ]);
        }

        $request->session()->put('authToken', $authToken = Str::random());
        $request->session()->put('deviceCode', $deviceCode->getKey());

        return $this->deviceAuthorizationViewResponse->withParameters([
            'client' => $deviceCode->client,
            'user' => $request->user(),
            'scopes' => Passport::scopesFor($deviceCode->scopes),
            'request' => $request,
            'authToken' => $authToken,
        ]);
    }

    /**
     * Approve the device authorization request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Laravel\Passport\Contracts\DeviceAuthorizationResultViewResponse
     */
    public function approve(Request $request)
    {
        $this->withErrorHandling(fn () => $this->server->completeDeviceAuthorizationRequest(
            $this->getDeviceCodeFromSession($request),
            $request->user()->getAuthIdentifier(),
            true
        ));

        return $this->deviceAuthorizationResultViewResponse->withParameters([
            'request' => $request,
            'approved' => true,
        ]);
    }

    /**
     * Deny the device authorization request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Laravel\Passport\Contracts\DeviceAuthorizationResultViewResponse
     */
    public function deny(Request $request)
    {
        $this->withErrorHandling(fn () => $this->server->completeDeviceAuthorizationRequest(
            $this->getDeviceCodeFromSession($request),
            $request->user()->getAuthIdentifier(),
            false
        ));

        return $this->deviceAuthorizationResultViewResponse->withParameters([
            'request' => $request,
            'approved' => false,
        ]);
    }
}
