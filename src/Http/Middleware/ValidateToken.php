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

abstract class ValidateToken
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(
        protected ResourceServer $server
    ) {
    }

    /**
     * Specify the parameters for the middleware.
     *
     * @param  string[]|string  ...$params
     */
    public static function using(...$params): string
    {
        if (is_array($params[0])) {
            return static::class.':'.implode(',', $params[0]);
        }

        return static::class.':'.implode(',', $params);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string[]|string  ...$params
     */
    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        $token = $this->validateToken($request);

        $this->validate($token, ...$params);

        return $next($request);
    }

    /**
     * Validate and get the request's access token.
     *
     * @throws \Laravel\Passport\Exceptions\AuthenticationException
     */
    protected function validateToken(Request $request): AccessToken
    {
        // If the user is authenticated and already has an access token set via
        // the token guard, there's no need to validate the request's bearer
        // token again, so we'll return the access token as the valid one.
        if ($request->user()?->token()) {
            return $request->user()->token();
        }

        // Otherwise, we will convert the request to a PSR-7 implementation and
        // pass it to the OAuth2 server to be validated. If the bearer token
        // passed the validation, we will return an access token instance.
        $psrRequest = (new PsrHttpFactory())->createRequest($request);

        try {
            $psrRequest = $this->server->validateAuthenticatedRequest($psrRequest);
        } catch (OAuthServerException) {
            throw new AuthenticationException;
        }

        return AccessToken::fromPsrRequest($psrRequest);
    }

    /**
     * Validate the given access token.
     *
     * @throws \Laravel\Passport\Exceptions\MissingScopeException
     */
    abstract protected function validate(AccessToken $token, string ...$params): void;
}
