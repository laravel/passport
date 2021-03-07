<?php

namespace Laravel\Passport\Events;

class AccessTokenCreated
{
    /**
     * The newly created token ID.
     *
     * @var string
     */
    public $tokenId;

    /**
     * The ID of the user associated with the token.
     *
     * @var string
     */
    public $userId;

    /**
     * The ID of the client associated with the token.
     *
     * @var string
     */
    public $clientId;

    /**
     * Create a new event instance.
     *
     * @param  string  $tokenId
     * @param  string|int|null  $userId
     * @param  string  $clientId
     * @return void
     */
    public function __construct($tokenId, $userId, $clientId)
    {
        $this->userId = $userId;
        $this->tokenId = $tokenId;
        $this->clientId = $clientId;
    }
}
