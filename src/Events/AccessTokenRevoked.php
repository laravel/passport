<?php

namespace Laravel\Passport\Events;

class AccessTokenRevoked
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $tokenId
    ) {
    }
}
