<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Passport\Contracts\DeviceUserCodeViewResponse;

class DeviceUserCodeController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected DeviceUserCodeViewResponse $viewResponse)
    {
    }

    /**
     * Show the form for entering the user code.
     */
    public function __invoke(Request $request): RedirectResponse|DeviceUserCodeViewResponse
    {
        if ($userCode = $request->query('user_code')) {
            return to_route('passport.device.authorizations.authorize', [
                'user_code' => $userCode,
            ]);
        }

        return $this->viewResponse->withParameters([
            'request' => $request,
        ]);
    }
}
