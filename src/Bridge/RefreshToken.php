<?php

namespace Laravel\Passport\Bridge;

use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\RefreshTokenTrait;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;

class RefreshToken implements RefreshTokenEntityInterface
{
    use EntityTrait, RefreshTokenTrait;
}
