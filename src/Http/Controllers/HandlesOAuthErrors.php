<?php

namespace Laravel\Passport\Http\Controllers;

use Closure;
use Laravel\Passport\Exceptions\OAuthServerException;
use League\OAuth2\Server\Exception\OAuthServerException as LeagueException;

trait HandlesOAuthErrors
{
    /**
     * Perform the given callback with exception handling.
     *
     * @template TResult
     *
     * @param  \Closure(): TResult  $callback
     * @return TResult
     *
     * @throws \Laravel\Passport\Exceptions\OAuthServerException
     */
    protected function withErrorHandling(Closure $callback, bool $useFragment = false)
    {
        try {
            return $callback();
        } catch (LeagueException $e) {
            throw new OAuthServerException($e, $useFragment);
        }
    }
}
