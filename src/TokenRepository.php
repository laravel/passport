<?php

namespace Laravel\Passport;

use Carbon\Carbon;

/**
 * @deprecated Will be removed in a future Laravel version.
 */
class TokenRepository
{
    /**
     * Creates a new Access Token.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @param  array  $attributes
     * @return \Laravel\Passport\Token
     */
    public function create($attributes)
    {
        return Passport::token()->create($attributes);
    }

    /**
     * Get a token by the given ID.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @param  string  $id
     * @return \Laravel\Passport\Token
     */
    public function find($id)
    {
        return Passport::token()->where('id', $id)->first();
    }

    /**
     * Get a token by the given user ID and token ID.
     *
     * @deprecated Use $user->tokens()->find()
     *
     * @param  string  $id
     * @param  int  $userId
     * @return \Laravel\Passport\Token|null
     */
    public function findForUser($id, $userId)
    {
        return Passport::token()->where('id', $id)->where('user_id', $userId)->first();
    }

    /**
     * Get the token instances for the given user ID.
     *
     * @deprecated User $user->tokens()
     *
     * @param  mixed  $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function forUser($userId)
    {
        return Passport::token()->where('user_id', $userId)->get();
    }

    /**
     * Get a valid token instance for the given user and client.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \Laravel\Passport\Client  $client
     * @return \Laravel\Passport\Token|null
     */
    public function getValidToken($user, $client)
    {
        return $client->tokens()
                    ->whereUserId($user->getAuthIdentifier())
                    ->where('revoked', 0)
                    ->where('expires_at', '>', Carbon::now())
                    ->first();
    }

    /**
     * Store the given token instance.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @param  \Laravel\Passport\Token  $token
     * @return void
     */
    public function save(Token $token)
    {
        $token->save();
    }

    /**
     * Revoke an access token.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @param  string  $id
     * @return mixed
     */
    public function revokeAccessToken($id)
    {
        return Passport::token()->where('id', $id)->update(['revoked' => true]);
    }

    /**
     * Check if the access token has been revoked.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @param  string  $id
     * @return bool
     */
    public function isAccessTokenRevoked($id)
    {
        return Passport::token()->where('id', $id)->where('revoked', 0)->doesntExist();
    }

    /**
     * Find a valid token for the given user and client.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \Laravel\Passport\Client  $client
     * @return \Laravel\Passport\Token|null
     */
    public function findValidToken($user, $client)
    {
        return $client->tokens()
                      ->whereUserId($user->getAuthIdentifier())
                      ->where('revoked', 0)
                      ->where('expires_at', '>', Carbon::now())
                      ->latest('expires_at')
                      ->first();
    }
}
