<?php

namespace Laravel\Passport\Observers;

use Laravel\Passport\Token;

use Illuminate\Support\Facades\Event;
use Laravel\Passport\Events\TokenRevoked;

class TokenObserver
{

    /**
     * Listen to the Token saved event.
     *
     * @param  \Laravel\Passport\Token $token
     * @return void
     */
    public function saved(Token $token)
    {
        return $this->dispatch($token);
    }
    
    /**
     * Listen to the Token updated event.
     *
     * @param  \Laravel\Passport\Token $token
     * @return void
     */
    public function updated(Token $token)
    {
        return $this->dispatch($token);
    }
    
    public function dispatch(Token $token)
    {
        if ($token->revoked) {
            Event::dispatch(
                new TokenRevoked($token->user, $token)
            );
        }
    }

}