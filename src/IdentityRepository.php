<?php

namespace Laravel\Passport;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use Laravel\Passport\Bridge\AccessTokenRepository;
use Laravel\Passport\Bridge\User;
use OpenIDConnectServer\Repositories\IdentityProviderInterface;

class IdentityRepository implements IdentityProviderInterface
{

    public function getUserEntityByIdentifier($identifier)
    {
        return new User($identifier);
    }
}