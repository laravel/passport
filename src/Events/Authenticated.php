<?php

namespace Laravel\Passport\Events;

class Authenticated
{
    /**
     * The newly created token ID.
     *
     * @var string
     */
    public $tokenId;

    /**
     * The user associated with the token.
     *
     * @var string
     */
    public $user;

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
     * @param  string  $user
     * @param  string  $clientId
     * @return void
     */
    public function __construct($tokenId, $user, $clientId)
    {
        $this->user = $user;
        $this->tokenId = $tokenId;
        $this->clientId = $clientId;
    }
}
