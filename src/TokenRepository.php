<?php

namespace Laravel\Passport;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

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
     * Get a valid token instance for the given user and client.
     *
     * @param  Model  $user
     * @param  Client  $client
     * @return Token|null
     */
    public function getValidToken($user, $client)
    {
        $provider = $this->getProviderFromUserInstance($user);

        return $client->tokens()
                    ->whereUserId($user->id)
                    ->whereProvider($provider)
                    ->whereRevoked(0)
                    ->where('expires_at', '>', Carbon::now())
                    ->first();
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
     * @return bool
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
     * Find a valid token for the given user and client.
     *
     * @param  Model  $user
     * @param  Client  $client
     * @return Token|null
     */
    public function findValidToken($user, $client)
    {
        $provider = $this->getProviderFromUserInstance($user);

        return $client->tokens()
                      ->whereUserId($user->id)
                      ->whereProvider($provider)
                      ->whereRevoked(0)
                      ->where('expires_at', '>', Carbon::now())
                      ->latest('expires_at')
                      ->first();
    }

    /**
     * Identifies the user model and finds the corresponding eloquent provider,
     * defaulting to "users"
     * @param $user
     * @return string
     */
    public function getProviderFromUserInstance($user)
    {
        $providers = config('auth.providers');

        foreach($providers as $provider => $config){
            if($config['driver'] == 'eloquent' &&  isset($config['model']) && get_class($user) == $config['model'] ){
                return $provider;
            }
        }
        return 'users';
    }
}
