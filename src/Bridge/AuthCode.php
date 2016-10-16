<?php

namespace Laravel\Passport\Bridge;

use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\AuthCodeTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;

class AuthCode implements AuthCodeEntityInterface
{
    use AuthCodeTrait, EntityTrait, TokenEntityTrait;
}
