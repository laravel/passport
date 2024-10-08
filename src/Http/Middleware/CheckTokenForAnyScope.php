<?php

namespace Laravel\Passport\Http\Middleware;

use Laravel\Passport\AccessToken;
use Laravel\Passport\Exceptions\MissingScopeException;

class CheckTokenForAnyScope extends ValidateToken
{
    /**
     * Determine if the token has at least one of the given scopes.
     *
     * @param  string[]  $scopes
     *
     * @throws \Laravel\Passport\Exceptions\MissingScopeException
     */
    protected function hasScopes(AccessToken $token, array $scopes): void
    {
        if (in_array('*', $token->oauth_scopes)) {
            return;
        }

        foreach ($scopes as $scope) {
            if ($token->can($scope)) {
                return;
            }
        }

        throw new MissingScopeException($scopes);
    }
}
