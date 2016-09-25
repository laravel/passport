<?php

namespace Laravel\Passport;

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;

class AuthCodeRepository
{
    /**
     * Get a auth code by the given ID.
     *
     * @param  int  $id
     * @return AuthCode|null
     */
    public function find($id)
    {
        return AuthCode::on(Passport::$connectionName)->find($id);
    }

    /**
     * Store a new auth code.
     *
     * @param  array  $attributes
     * @return AuthCode
     */
    public function create(array $attributes)
    {
        $authCode = (new AuthCode)->on(Passport::$connectionName)
            ->forceFill($attributes)
            ->save();

        return $authCode;
    }

    /**
     * Revoke an auth code.
     *
     * @param  string  $id
     * @return bool|int
     */
    public function revoke($id)
    {
        return $this->find($id)->update(['revoked' => true]);
    }

    /**
     * Determine if the given auth code is revoked.
     *
     * @param  int  $id
     * @return bool
     */
    public function revoked($id)
    {
        return AuthCode::on(Passport::$connectionName)
            ->where('id', $id)
            ->where('revoked', true)
            ->exists();
    }
}
