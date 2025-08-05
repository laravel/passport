<?php

namespace Laravel\Passport\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Laravel\Passport\Exceptions\InvalidAuthTokenException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;

trait RetrievesAuthRequestFromSession
{
    /**
     * Get the authorization request from the session.
     *
     * @throws \Laravel\Passport\Exceptions\InvalidAuthTokenException
     * @throws \Exception
     */
    protected function getAuthRequestFromSession(Request $request): AuthorizationRequestInterface
    {
        if ($request->isNotFilled('auth_token') ||
            $request->session()->pull('authToken') !== $request->get('auth_token')) {
            $request->session()->forget(['authToken', 'authRequest']);

            throw InvalidAuthTokenException::different();
        }

        return $request->session()->pull('authRequest')
            ?? throw new Exception('Authorization request was not present in the session.');
    }
}
