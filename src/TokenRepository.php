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
     * @param  mixed  $clientId
     * @param  mixed  $userId
     * @param  bool  $prune
     * @return void
     */
    public function revokeOtherAccessTokens($clientId, $userId, $except = null, $prune = false)
    {
        $query = Token::where('user_id', $userId)
                      ->where('client_id', $clientId);

        if ($except) {
            $query->where('id', '<>', $except);
        }

        if ($prune) {
            $query->delete();
        } else {
            $query->update(['revoked' => true]);
        }
    }
}
