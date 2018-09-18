<?php

namespace Laravel\Passport\Bridge;

use League\OAuth2\Server\Entities\Traits\{ EntityTrait, AccessTokenTrait, TokenEntityTrait };
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;

class AccessToken implements AccessTokenEntityInterface
{
    use AccessTokenTrait, EntityTrait, TokenEntityTrait;

    /**
     * Create a new token instance.
     *
     * @param  string|int  $userIdentifier
     * @param  array  $scopes
     * @return void
     */
    public function __construct($userIdentifier, array $scopes = [])
    {
        $this->setUserIdentifier($userIdentifier);

        foreach ($scopes as $scope) {
            $this->addScope($scope);
        }
    }
}
