<?php

namespace ROMaster2\Passport\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use ROMaster2\Passport\Bridge\User;

trait RetrievesAuthRequestFromSession
{
    /**
     * Get the authorization request from the session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \League\OAuth2\Server\RequestTypes\AuthorizationRequest
     * @throws \Exception
     */
    protected function getAuthRequestFromSession(Request $request)
    {
        return tap($request->session()->get('authRequest'), function ($authRequest) use ($request) {
            if (! $authRequest) {
                throw new Exception('Authorization request was not present in the session.');
            }

            $authRequest->setUser(new User($request->user()->getKey()));

            $authRequest->setAuthorizationApproved(true);
        });
    }
}
