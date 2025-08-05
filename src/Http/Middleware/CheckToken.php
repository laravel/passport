<?php

namespace Laravel\Passport\Http\Middleware;

use Laravel\Passport\Contracts\ScopeAuthorizable;
use Laravel\Passport\Exceptions\MissingScopeException;

class CheckToken extends ValidateToken
{
    /**
     * Determine if the token has all the given scopes.
     *
     * @throws \Laravel\Passport\Exceptions\MissingScopeException
     */
    protected function validate(ScopeAuthorizable $token, string ...$params): void
    {
        foreach ($params as $scope) {
            if ($token->cant($scope)) {
                throw new MissingScopeException($scope);
            }
        }
    }
}
