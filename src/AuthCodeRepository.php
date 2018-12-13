<?php

namespace Laravel\Passport;

class AuthCodeRepository
{
    /**
     * Creates a new auth code
     *
     * @param  array  $attributes
     * @return \Laravel\Passport\AuthCode
     */
    public function create($attributes)
    {
        return Passport::authCode()->create($attributes);
    }

    /**
     * Revoke a auth code
     *
     * @param  string  $codeId
     * @return mixed
     */
    public function revokeAuthCode($codeId)
    {
        return Passport::authCode()->where('id', $codeId)->update(['revoked' => true]);
    }

    /**
     * Check if the auth code has been revoked.
     *
     * @param  string  $codeId
     *
     * @return bool Return true if this code has been revoked
     */
    public function isAuthCodeRevoked($codeId)
    {
        return Passport::authCode()->where('id', $codeId)->where('revoked', 1)->exists();
    }
}
