<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Passport\Contracts\DeviceAuthorizationViewResponse;
use Laravel\Passport\Passport;

class DeviceAuthorizationController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected DeviceAuthorizationViewResponse $viewResponse)
    {
    }

    /**
     * Authorize a device to access the user's account.
     */
    public function __invoke(Request $request): RedirectResponse|DeviceAuthorizationViewResponse
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

        return $this->viewResponse->withParameters([
            'client' => $deviceCode->client,
            'user' => $request->user(),
            'scopes' => Passport::scopesFor($deviceCode->scopes),
            'request' => $request,
            'authToken' => $authToken,
        ]);
    }
}
