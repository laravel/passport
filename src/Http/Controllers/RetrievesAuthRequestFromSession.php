<?php

namespace Laravel\Passport\Http\Controllers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Laravel\Passport\Bridge\User;

trait RetrievesAuthRequestFromSession
{
    /**
     * Get the authorization request from the session.
     *
     * @param  Request  $request
     * @return AuthorizationRequest
     */
    protected function getAuthRequestFromSession(Request $request)
    {
        return tap($request->session()->get('authRequest'), function ($authRequest) use ($request) {
            if (! $authRequest) {
                throw new Exception('Authorization request was not present in the session.');
            }

            $userId = $request->user() instanceof Model ? $request->user()->getKey() : $request->user()->id;

            $authRequest->setUser(new User($userId));

            $authRequest->setAuthorizationApproved(true);
        });
    }
}
