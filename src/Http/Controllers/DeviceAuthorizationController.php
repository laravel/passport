<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Passport\Contracts\AuthorizationViewResponse;
use Laravel\Passport\Contracts\DeviceCodeViewResponse;
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
     * The authorization view response implementation.
     *
     * @var \Laravel\Passport\Contracts\DeviceCodeViewResponse
     */
    protected $deviceCodeViewResponse;

    /**
     * The authorization view response implementation.
     *
     * @var \Laravel\Passport\Contracts\AuthorizationViewResponse
     */
    protected $authorizationViewResponse;

    /**
     * Create a new controller instance.
     *
     * @param  \League\OAuth2\Server\AuthorizationServer  $server
     * @param  \Laravel\Passport\Contracts\DeviceCodeViewResponse  $deviceCodeViewResponse
     * @param  \Laravel\Passport\Contracts\AuthorizationViewResponse  $authorizationViewResponse
     * @return void
     */
    public function __construct(AuthorizationServer $server,
                                DeviceCodeViewResponse $deviceCodeViewResponse,
                                AuthorizationViewResponse $authorizationViewResponse)
    {
        $this->server = $server;
        $this->deviceCodeViewResponse = $deviceCodeViewResponse;
        $this->authorizationViewResponse = $authorizationViewResponse;
    }

    /**
     * Show the form for entering user code.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Laravel\Passport\Contracts\DeviceCodeViewResponse
     */
    public function userCode(Request $request)
    {
        if ($userCode = $request->query('user_code')) {
            return to_route('passport.device.authorize', [
                'user_code' => $userCode,
            ]);
        }

        return $this->deviceCodeViewResponse;
    }

    /**
     * Authorize a client to access the user's account.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Laravel\Passport\Contracts\AuthorizationViewResponse
     */
    public function authorize(Request $request)
    {
        $deviceCode = Passport::deviceCode()
            ->with('client')
            ->where('user_code', $userCode = $request->query('user_code'))
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

        return $this->authorizationViewResponse->withParameters([
            'client' => $deviceCode->client,
            'user' => $request->user(),
            'scopes' => Passport::scopesFor($deviceCode->scopes),
            'request' => $request,
            'authToken' => $authToken,
        ]);
    }

    /**
     * Approve the authorization request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Laravel\Passport\Exceptions\OAuthServerException
     */
    public function approve(Request $request)
    {
        $this->withErrorHandling(fn () => $this->server->completeDeviceAuthorizationRequest(
            $this->getDeviceCodeFromSession($request),
            $request->user()->getAuthIdentifier(),
            true
        ));

        return 'approved';
    }

    /**
     * Deny the authorization request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Laravel\Passport\Exceptions\OAuthServerException
     */
    public function deny(Request $request)
    {
        $this->withErrorHandling(fn () => $this->server->completeDeviceAuthorizationRequest(
            $this->getDeviceCodeFromSession($request),
            $request->user()->getAuthIdentifier(),
            false
        ));

        return 'denied';
    }
}
