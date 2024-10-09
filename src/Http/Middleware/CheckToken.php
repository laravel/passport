<?php

namespace Laravel\Passport\Http\Middleware;

use Laravel\Passport\AccessToken;
use Laravel\Passport\Exceptions\MissingScopeException;

class CheckToken extends ValidateToken
{
    /**
     * Determine if the token has all the given scopes.
     *
     * @throws \Laravel\Passport\Exceptions\MissingScopeException
     */
    protected function validate(AccessToken $token, string ...$params): void
    {
        foreach ($params as $scope) {
            if ($token->cant($scope)) {
                throw new MissingScopeException($scope);
            }
        }
    }
}
