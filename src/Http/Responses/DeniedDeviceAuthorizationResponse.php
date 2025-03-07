<?php

namespace Laravel\Passport\Http\Responses;

use Laravel\Passport\Contracts\DeniedDeviceAuthorizationResponse as DeniedDeviceAuthorizationResponseContract;

class DeniedDeviceAuthorizationResponse implements DeniedDeviceAuthorizationResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        return to_route('passport.device')
            ->with('status', 'authorization-denied');
    }
}
