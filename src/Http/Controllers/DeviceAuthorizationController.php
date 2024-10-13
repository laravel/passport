<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Laravel\Passport\Contracts\DeviceAuthorizationViewResponse;
use Laravel\Passport\Passport;

class DeviceAuthorizationController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected StatefulGuard $guard
    ) {
    }

    /**
     * Authorize a device to access the user's account.
     */
    public function __invoke(
        Request $request,
        DeviceAuthorizationViewResponse $viewResponse
    ): RedirectResponse|DeviceAuthorizationViewResponse {
        if (! $userCode = $request->query('user_code')) {
            return to_route('passport.device');
        }

        $deviceCode = Passport::deviceCode()
            ->with('client')
            ->where('user_code', $userCode)
            ->where('expires_at', '>', Date::now())
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

        return $viewResponse->withParameters([
            'client' => $deviceCode->client,
            'user' => $this->guard->user(),
            'scopes' => Passport::scopesFor($deviceCode->scopes),
            'request' => $request,
            'authToken' => $authToken,
        ]);
    }
}
