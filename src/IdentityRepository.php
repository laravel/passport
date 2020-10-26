<?php

namespace Laravel\Passport;

use Laravel\Passport\Bridge\User;
use OpenIDConnectServer\Repositories\IdentityProviderInterface;

class IdentityRepository implements IdentityProviderInterface
{
    /**
     * @param $identifier
     * @return User
     */
    public function getUserEntityByIdentifier($identifier)
    {
        return new User($identifier);
    }
}