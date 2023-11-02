<?php

namespace Laravel\Passport\Http\Middleware;

use Laravel\Passport\Exceptions\AuthenticationException;
use Laravel\Passport\Exceptions\MissingScopeException;

class CheckScopes
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$scopes
     * @return \Illuminate\Http\Response
     *
     * @throws \Laravel\Passport\Exceptions\AuthenticationException|\Laravel\Passport\Exceptions\MissingScopeException
     */
    public function handle($request, $next, ...$scopes)
    {
        if (! $request->user() || ! $request->user()->token()) {
            throw new AuthenticationException;
        }

        foreach ($scopes as $scope) {
            if (! $request->user()->tokenCan($scope)) {
                throw new MissingScopeException($scope);
            }
        }

        return $next($request);
    }

    /**
     * Generate a string representation of the middleware with specified scopes.
     *
     * @param  array  $scopes 
     * @return string
     */
    public static function using(...$scopes)
    {
        return static::class . ':' . implode(',', $scopes);
    }
}
