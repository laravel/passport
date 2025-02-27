<?php

namespace Laravel\Passport;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;

/**
 * @deprecated Will be removed in a future Laravel version.
 */
class TokenRepository
{
    /**
     * Get a token by the given user ID and token ID.
     *
     * @deprecated Use $user->tokens()->find()
     *
     * @param  \Laravel\Passport\Contracts\OAuthenticatable  $user
     */
    public function findForUser(string $id, Authenticatable $user): ?Token
    {
        return $user->tokens()
            ->with('client')
            ->where('revoked', false)
            ->where('expires_at', '>', Date::now())
            ->find($id);
    }

    /**
     * Get the token instances for the given user ID.
     *
     * @deprecated Use $user->tokens()
     *
     * @param  \Laravel\Passport\Contracts\OAuthenticatable  $user
     * @return \Illuminate\Database\Eloquent\Collection<int, \Laravel\Passport\Token>
     */
    public function forUser(Authenticatable $user): Collection
    {
        return $user->tokens()
            ->with('client')
            ->where('revoked', false)
            ->where('expires_at', '>', Date::now())
            ->get();
    }
}
