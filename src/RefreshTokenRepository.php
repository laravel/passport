<?php

namespace Laravel\Passport;

class RefreshTokenRepository
{
    use RepositoryHelper;

    /**
     * The refresh token model.
     *
     * @var string
     */
    protected static $model = RefreshToken::class;

    /**
     * Get a token by the given ID.
     *
     * @param  string  $id
     * @return RefreshToken
     */
    public function find($id)
    {
        return $this->createModel()->find($id);
    }

    /**
     * Creates a new Access Token
     *
     * @param  array  $attributes
     * @return RefreshToken
     */
    public function create(array $attributes)
    {
        $authCode = $this->createModel()
            ->forceFill($attributes)
            ->save();

        return $authCode;
    }

    /**
     * Revoke a refresh token.
     *
     * @param  string  $id
     * @return bool|int
     */
    public function revoke($id)
    {
        return $this->find($id)->update(['revoked' => true]);
    }

    /**
     * Check if the given refresh token has been revoked.
     *
     * @param  string  $id
     * @return bool
     */
    public function revoked($id)
    {
        return $this->createModel()
            ->where('id', $id)
            ->where('revoked', 1)
            ->exists();
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
