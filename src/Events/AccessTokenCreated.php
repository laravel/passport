<?php

namespace Laravel\Passport\Events;

class AccessTokenCreated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $tokenId,
        public ?string $userId,
        public string $clientId
    ) {
    }
}
