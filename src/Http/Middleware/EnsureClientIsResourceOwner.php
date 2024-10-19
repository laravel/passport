<?php

namespace Laravel\Passport\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Exceptions\MissingScopeException;

class EnsureClientIsResourceOwner extends ValidateToken
{
    /**
     * Determine if the token's client is the resource owner and has all the given scopes.
     *
     * @throws \Exception
     */
    protected function validate(AccessToken $token, string ...$params): void
    {
        if ($token->oauth_user_id !== $token->oauth_client_id) {
            throw new AuthenticationException;
        }

        foreach ($params as $scope) {
            if ($token->cant($scope)) {
                throw new MissingScopeException($scope);
            }
        }
    }
}
