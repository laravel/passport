<?php

namespace Laravel\Passport\Events;

class TokenRevoked
{
    /**
     * The token object.
     *
     * @var \Laravel\Passport\Token
     */
    public $token;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param  string  $tokenId
     * @param  string  $userId
     * @return void
     */
    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->token = $token;
    }
}
