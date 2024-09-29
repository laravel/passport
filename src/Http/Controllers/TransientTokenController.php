<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\ApiTokenCookieFactory;

class TransientTokenController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ApiTokenCookieFactory $cookieFactory
    ) {
    }

    /**
     * Get a fresh transient token cookie for the authenticated user.
     */
    public function refresh(Request $request): Response
    {
        return (new Response('Refreshed.'))->withCookie($this->cookieFactory->make(
            $request->user()->getAuthIdentifier(), $request->session()->token()
        ));
    }
}
