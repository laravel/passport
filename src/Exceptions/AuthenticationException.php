<?php

namespace Laravel\Passport\Exceptions;

use Illuminate\Auth\AuthenticationException as Exception;

class AuthenticationException extends Exception
{
    static function make(): self
    {
        return new self(redirectTo: config('passport.login_route', route('login')));
    }
}
