<?php

namespace Laravel\Passport\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Laravel\Passport\Exceptions\InvalidAuthTokenException;

trait RetrievesDeviceCodeFromSession
{
    /**
     * Get the device code from the session.
     *
     * @throws \Laravel\Passport\Exceptions\InvalidAuthTokenException
     * @throws \Exception
     */
    protected function getDeviceCodeFromSession(Request $request): string
    {
        if ($request->isNotFilled('auth_token') || $request->session()->pull('authToken') !== $request->get('auth_token')) {
            $request->session()->forget(['authToken', 'deviceCode']);

            throw InvalidAuthTokenException::different();
        }

        return tap($request->session()->pull('deviceCode'), function ($deviceCode) {
            if (! $deviceCode) {
                throw new Exception('Device code was not present in the session.');
            }

            return $deviceCode;
        });
    }
}
