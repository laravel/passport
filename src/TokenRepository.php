<?php

namespace Laravel\Passport;

class TokenRepository
{
    /**
     * Get a token by the given ID.
     *
     * @param  string  $id
     * @return Token
     */
    public function find($id)
    {
        return Token::find($id);
    }

    /**
     * Store the given token instance.
     *
     * @param  Token  $token
     * @return void
     */
    public function save($token)
    {
        $token->save();
    }
}
