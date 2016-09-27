<?php

namespace Laravel\Passport;

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;

class AuthCodeRepository
{
    use RepositoryTrait;

    /**
     * The auth code model.
     *
     * @var string
     */
    protected static $model = AuthCode::class;

    /**
     * Get a auth code by the given ID.
     *
     * @param  int  $id
     * @return AuthCode|null
     */
    public function find($id)
    {
        return $this->createModel()->find($id);
    }

    /**
     * Store a new auth code.
     *
     * @param  array  $attributes
     * @return AuthCode
     */
    public function create(array $attributes)
    {
        $authCode = $this->createModel()->forceFill($attributes);

        $authCode->save();

        return $authCode;
    }

    /**
     * Revoke an auth code.
     *
     * @param  string  $id
     * @return bool|int
     */
    public function revoke($id)
    {
        return $this->find($id)->update(['revoked' => true]);
    }

    /**
     * Determine if the given auth code is revoked.
     *
     * @param  int  $id
     * @return bool
     */
    public function revoked($id)
    {
        return $this->createModel()
            ->where('id', $id)
            ->where('revoked', true)
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
