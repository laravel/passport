<?php

namespace Laravel\Passport\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Laravel\Passport\Exceptions\InvalidAuthTokenException;

trait RetrievesDeviceCodeFromSession
{
    /**
     * Make sure the auth token matches the one in the session.
     *
     * @throws \Laravel\Passport\Exceptions\InvalidAuthTokenException
     */
    protected function assertValidDeviceCode(Request $request): void
    {
        if (! $request->has('auth_token') || $request->session()->get('authToken') !== $request->get('auth_token')) {
            $request->session()->forget(['authToken', 'deviceCode']);

            throw InvalidAuthTokenException::different();
        }
    }

    /**
     * Get the device code from the session.
     *
     * @throws \Exception
     */
    protected function getDeviceCodeFromSession(Request $request): string
    {
        return tap($request->session()->pull('deviceCode'), function ($deviceCode) {
            if (! $deviceCode) {
                throw new Exception('Device code was not present in the session.');
            }

            return $deviceCode;
        });
    }
}
