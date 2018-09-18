<?php

namespace Laravel\Passport\Bridge;

use League\OAuth2\Server\Entities\Traits\{ EntityTrait, AuthCodeTrait, TokenEntityTrait };
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;

class AuthCode implements AuthCodeEntityInterface
{
    use AuthCodeTrait, EntityTrait, TokenEntityTrait;
}
