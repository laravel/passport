<?php

namespace Laravel\Passport;

use Carbon\Carbon;

class TokenRepository
{
    /**
     * Creates a new Access Token
     *
     * @param  array  $attributes
     * @return Token
     */
    public function create($attributes)
    {
        return Token::create($attributes);
    }

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
    public function save(Token $token)
    {
        $token->save();
    }

    /**
     * Revoke an access token.
     *
     * @param string $id
     */
    public function revokeAccessToken($id)
    {
        return $this->find($id)->update(['revoked' => true]);
    }

    /**
     * Check if the access token has been revoked.
     *
     * @param string $id
     *
     * @return bool Return true if this token has been revoked
     */
    public function isAccessTokenRevoked($id)
    {
        if ($token = $this->find($id)) {
            return $token->revoked;
        }

        return true;
    }
    
    /**
     * Store the given token instance.
     *
     * @param  Model  $userId
     * @param  Client  $client
     * @return Token|null
     */
    public function getValidToken($user, $client)
    {
        return $client->tokens()
            ->whereUserId($user->id)
            ->whereRevoked(0)
            ->where('expires_at', '>', Carbon::now())
            ->first();
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
