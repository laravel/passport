<?php

namespace Laravel\Passport\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\Exceptions\AuthenticationException;
use Laravel\Passport\Exceptions\MissingScopeException;
use Symfony\Component\HttpFoundation\Response;

class CheckScopes
{
    /**
     * Specify the scopes for the middleware.
     *
     * @param  string[]|string  ...$scopes
     */
    public static function using(...$scopes): string
    {
        if (is_array($scopes[0])) {
            return static::class.':'.implode(',', $scopes[0]);
        }

        return static::class.':'.implode(',', $scopes);
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     *
     * @throws \Laravel\Passport\Exceptions\AuthenticationException|\Laravel\Passport\Exceptions\MissingScopeException
     */
    public function handle(Request $request, Closure $next, string ...$scopes): Response
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
}
