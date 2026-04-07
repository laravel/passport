<?php

namespace Laravel\Passport\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Laravel\Passport\Exceptions\InvalidAuthTokenException;
use League\OAuth2\Server\Entities\DeviceCodeEntityInterface;

trait RetrievesDeviceCodeFromSession
{
    /**
     * Get the device code from the session.
     *
     * @throws \Laravel\Passport\Exceptions\InvalidAuthTokenException
     * @throws \Exception
     */
    protected function getDeviceCodeFromSession(Request $request): DeviceCodeEntityInterface
    {
        if ($request->isNotFilled('auth_token') ||
            $request->session()->pull('authToken') !== $request->input('auth_token')) {
            $request->session()->forget(['authToken', 'deviceCode']);

            throw InvalidAuthTokenException::different();
        }

        $deviceCode = $request->session()->pull('deviceCode')
            ?? throw new Exception('Device code was not present in the session.');

        return unserialize($deviceCode, ['allowed_classes' => [
            \Laravel\Passport\Bridge\DeviceCode::class,
            \Laravel\Passport\Bridge\Client::class,
            \Laravel\Passport\Bridge\Scope::class,
            \DateTimeImmutable::class,
        ]]);
    }
}
