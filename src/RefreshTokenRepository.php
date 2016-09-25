<?php

namespace Laravel\Passport;

class RefreshTokenRepository
{
    /**
     * Get a token by the given ID.
     *
     * @param  string  $id
     * @return RefreshToken
     */
    public function find($id)
    {
        return RefreshToken::on(Passport::$connectionName)->find($id);
    }

    /**
     * Creates a new Access Token
     *
     * @param  array  $attributes
     * @return RefreshToken
     */
    public function create(array $attributes)
    {
        $authCode = (new RefreshToken)->on(Passport::$connectionName)
            ->forceFill($attributes)
            ->save();

        return $authCode;
    }

    /**
     * Revoke a refresh token.
     *
     * @param  string  $id
     * @return bool|int
     */
    public function revoke($id)
    {
        return $this->find($id)->update(['revoked' => true]);
    }

    /**
     * Check if the given refresh token has been revoked.
     *
     * @param  string  $id
     * @return bool
     */
    public function revoked($id)
    {
        return RefreshToken::on(Passport::$connectionName)
            ->where('id', $id)
            ->where('revoked', 1)
            ->exists();
    }
}
