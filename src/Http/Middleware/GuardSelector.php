<?php

namespace Laravel\Passport\Http\Middleware;

use Closure;
use Laravel\Passport\Passport;

class GuardSelector
{
    public function handle($request, Closure $next, $param = 'api')
    {
        $callback = Passport::getGuardResolver();
        $guard = $callback ? $callback($request, $param) : $param;
        app('auth')->shouldUse($guard);

        return $next($request);
    }
}
