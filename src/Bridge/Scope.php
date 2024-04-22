<?php

namespace Laravel\Passport\Bridge;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use ReturnTypeWillChange;

class Scope implements ScopeEntityInterface
{
    use EntityTrait;

    /**
     * Create a new scope instance.
     */
    public function __construct(string $name)
    {
        $this->setIdentifier($name);
    }

    /**
     * Get the data that should be serialized to JSON.
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize(): string
    {
        return $this->getIdentifier();
    }
}
