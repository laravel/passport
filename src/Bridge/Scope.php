<?php

namespace Laravel\Passport\Bridge;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\ScopeTrait;

class Scope implements ScopeEntityInterface
{
    use ScopeTrait, EntityTrait;

    /**
     * Create a new scope instance.
     */
    public function __construct(string $name)
    {
        $this->setIdentifier($name);
    }
}
