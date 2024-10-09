<?php

namespace Laravel\Passport\Http\Middleware;

use Laravel\Passport\AccessToken;
use Laravel\Passport\Exceptions\MissingScopeException;

class CheckTokenForAnyScope extends ValidateToken
{
    /**
     * Determine if the token has at least one of the given scopes.
     *
     * @throws \Laravel\Passport\Exceptions\MissingScopeException
     */
    protected function validate(AccessToken $token, string ...$params): void
    {
        foreach ($params as $scope) {
            if ($token->can($scope)) {
                return;
            }
        }

        throw new MissingScopeException($params);
    }
}
