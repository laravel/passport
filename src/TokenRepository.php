<?php

namespace Laravel\Passport;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TokenRepository
{
    use RepositoryTrait;

    /**
     * Model instance
     *
     * @var \Illuminate\Database\Eloquent\Model $model
     */
    protected $model;

    /**
     * TokenRepository constructor.
     */
    public function __construct()
    {
        $this->model = config('passport.tokens.model');
    }

    /**
     * Creates a new Access Token.
     *
     * @param  array  $attributes
     * @return \Laravel\Passport\Token
     */
    public function create($attributes)
    {
        return $this->modelInstance()->create($attributes);
    }

    /**
     * Get a token by the given ID.
     *
     * @param  string  $id
     * @return \Laravel\Passport\Token
     */
    public function find($id)
    {
        return $this->modelInstance()->where('id', $id)->first();
    }

    /**
     * Get a token by the given user ID and token ID.
     *
     * @param  string  $id
     * @param  int  $userId
     * @return \Laravel\Passport\Token|null
     */
    public function findForUser($id, $userId)
    {
        return $this->modelInstance()->where('id', $id)->where('user_id', $userId)->first();
    }

    /**
     * Get the token instances for the given user ID.
     *
     * @param  mixed  $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function forUser($userId)
    {
        return $this->modelInstance()->where('user_id', $userId)->get();
    }

    /**
     * Get a valid token instance for the given user and client.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  \Laravel\Passport\Client  $client
     * @return \Laravel\Passport\Token|null
     */
    public function getValidToken($user, $client)
    {
        return $client->tokens()
                    ->whereUserId($user->getKey())
                    ->whereRevoked(0)
                    ->where('expires_at', '>', Carbon::now())
                    ->first();
    }

    /**
     * Store the given token instance.
     *
     * @param  \Laravel\Passport\Token  $token
     * @return void
     */
    public function save(Token $token)
    {
        $token->save();
    }

    /**
     * Revoke an access token.
     *
     * @param  string  $id
     * @return mixed
     */
    public function revokeAccessToken($id)
    {
        return $this->find($id)->update(['revoked' => true]);
    }

    /**
     * Check if the access token has been revoked.
     *
     * @param  string  $id
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
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  \Laravel\Passport\Client  $client
     * @return \Laravel\Passport\Token|null
     */
    public function findValidToken($user, $client)
    {
        return $client->tokens()
                      ->whereUserId($user->getKey())
                      ->whereRevoked(0)
                      ->where('expires_at', '>', Carbon::now())
                      ->latest('expires_at')
                      ->first();
    }
}
