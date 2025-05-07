<?php

namespace Laravel\Passport\Http\Middleware;

use Laravel\Passport\AccessToken;
use Laravel\Passport\Contracts\ScopeAuthorizable;
use Laravel\Passport\Exceptions\AuthenticationException;
use Laravel\Passport\Exceptions\MissingScopeException;

class EnsureClientIsResourceOwner extends ValidateToken
{
    /**
     * Determine if the token's client is the resource owner and has all the given scopes.
     *
     * @throws \Laravel\Passport\Exceptions\AuthenticationException|\Laravel\Passport\Exceptions\MissingScopeException
     */
    protected function validate(ScopeAuthorizable $token, string ...$params): void
    {
        if (
            $token instanceof AccessToken
            && ! is_null($token->oauth_user_id)
            && $token->oauth_user_id !== $token->oauth_client_id
        ) {
            throw new AuthenticationException;
        }

        foreach ($params as $scope) {
            if ($token->cant($scope)) {
                throw new MissingScopeException($scope);
            }
        }
    }
}
