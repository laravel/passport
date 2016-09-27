<?php

namespace Laravel\Passport;

class TokenRepository
{
    use RepositoryTrait;

    /**
     * The access token model.
     *
     * @var string
     */
    protected static $model = Token::class;

    /**
     * Creates a new Access Token
     *
     * @param  array  $attributes
     * @return Token
     */
    public function create($attributes)
    {
        return $this->createModel()->create($attributes);
    }

    /**
     * Get a token by the given ID.
     *
     * @param  string  $id
     * @return Token
     */
    public function find($id)
    {
        return $this->createModel()->find($id);
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
        return $this->createModel()->where('id', $id)->where('revoked', 1)->exists();
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
        $query = $this->createModel()
            ->where('user_id', $userId)
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

    /**
     * {@inheritdoc}
     */
    public static function getModel()
    {
        return static::$model;
    }

    /**
     * {@inheritdoc}
     */
    public static function setModel($model)
    {
        static::$model = $model;

        return new static;
    }
}
