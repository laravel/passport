<?php

namespace Laravel\Passport\Bridge;

use Illuminate\Http\Request;

class AuthGuard
{
    /**
     * Get User Provider
     *
     * @return string
     */
    public static function provider()
    {
        return config('auth.guards.api.provider');
    }

    /**
     * Get User Provider Model
     *
     * @return string
     */
    public static function model()
    {
        return config('auth.providers.'.AuthGuard::provider().'.model');
    }
}
