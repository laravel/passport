<?php

namespace Laravel\Passport\Bridge;

use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\UserEntityInterface;

class User implements UserEntityInterface
{
    use EntityTrait;

    /**
     * Create a new user instance.
     *
     * @param  non-empty-string  $identifier
     */
    public function __construct(string $identifier)
    {
        $this->setIdentifier($identifier);
    }
}
