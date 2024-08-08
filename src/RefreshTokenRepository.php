<?php

namespace Laravel\Passport;

/**
 * @deprecated Will be removed in a future Laravel version.
 */
class RefreshTokenRepository
{
    /**
     * Creates a new refresh token.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @param  array  $attributes
     * @return \Laravel\Passport\RefreshToken
     */
    public function create($attributes)
    {
        return Passport::refreshToken()->create($attributes);
    }

    /**
     * Gets a refresh token by the given ID.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @param  string  $id
     * @return \Laravel\Passport\RefreshToken
     */
    public function find($id)
    {
        return Passport::refreshToken()->where('id', $id)->first();
    }

    /**
     * Stores the given token instance.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @param  \Laravel\Passport\RefreshToken  $token
     * @return void
     */
    public function save(RefreshToken $token)
    {
        $token->save();
    }

    /**
     * Revokes the refresh token.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @param  string  $id
     * @return mixed
     */
    public function revokeRefreshToken($id)
    {
        return Passport::refreshToken()->where('id', $id)->update(['revoked' => true]);
    }

    /**
     * Revokes refresh tokens by access token id.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @param  string  $tokenId
     * @return mixed
     */
    public function revokeRefreshTokensByAccessTokenId($tokenId)
    {
        return Passport::refreshToken()->where('access_token_id', $tokenId)->update(['revoked' => true]);
    }

    /**
     * Checks if the refresh token has been revoked.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @param  string  $id
     * @return bool
     */
    public function isRefreshTokenRevoked($id)
    {
        return Passport::refreshToken()->where('id', $id)->where('revoked', 0)->doesntExist();
    }
}
