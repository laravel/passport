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

    /**
     * Revoke all of the access tokens for a given user and client.
     *
     * @deprecated since 1.0. Listen to Passport events on token creation instead.
     *
     * @param  mixed  $clientId
     * @param  mixed  $userId
     * @param  bool  $prune
     * @return void
     */
    public function revokeOtherAccessTokens($clientId, $userId, $except = null, $prune = false)
    {
        //
    }
}
