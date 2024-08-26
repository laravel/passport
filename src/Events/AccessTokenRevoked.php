<?php

namespace Laravel\Passport\Events;

class AccessTokenRevoked
{
    /**
     * Create a new event instance.
     *
     * @param  string  $tokenId
     * @return void
     */
    public function __construct(
        public string $tokenId,
    ) {
    }
}
