<?php

namespace Laravel\Passport\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Laravel\Passport\AccessToken;

class EnsureClientIsResourceOwner extends ValidateToken
{
    /**
     * Determine if the token's client is the resource owner.
     *
     * @throws \Exception
     */
    protected function validate(AccessToken $token, string ...$params): void
    {
        if ($token->oauth_user_id !== $token->oauth_client_id) {
            throw new AuthenticationException;
        }
    }
}
