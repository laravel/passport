<?php

namespace Laravel\Passport\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Exceptions\AuthenticationException;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Response;

abstract class CheckCredentials
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(
        protected ResourceServer $server
    ) {
    }

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
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string[]|string  ...$scopes
     *
     * @throws \Laravel\Passport\Exceptions\AuthenticationException
     */
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        $psr = (new PsrHttpFactory())->createRequest($request);

        try {
            $psr = $this->server->validateAuthenticatedRequest($psr);
        } catch (OAuthServerException) {
            throw new AuthenticationException;
        }

        $this->validateScopes(AccessToken::fromPsrRequest($psr), $scopes);

        return $next($request);
    }

    /**
     * Validate token scopes.
     *
     * @param  string[]  $scopes
     *
     * @throws \Laravel\Passport\Exceptions\MissingScopeException
     */
    abstract protected function validateScopes(AccessToken $token, array $scopes): void;
}
