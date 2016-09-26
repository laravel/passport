<?php

namespace Laravel\Passport;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'oauth_clients';

    /**
     * The guarded attributes on the model.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'personal_access_client' => 'bool',
        'password_client' => 'bool',
        'revoked' => 'bool',
    ];

    /**
     * The auth code relation model.
     *
     * @var string
     */
    protected static $authCodeModel = AuthCode::class;

    /**
     * The access token relation model.
     *
     * @var string
     */
    protected static $tokenModel = Token::class;

    /**
     * Get all of the authentication codes for the client.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function authCodes()
    {
        return $this->hasMany(static::$authCodeModel);
    }

    /**
     * Get all of the tokens that belong to the client.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tokens()
    {
        return $this->hasMany(static::$tokenModel);
    }

    /**
     * Determine if the client is a "first party" client.
     *
     * @return bool
     */
    public function firstParty()
    {
        return $this->personal_access_client || $this->password_client;
    }

    /**
     * Get the auth code model.
     *
     * @return mixed
     */
    public static function getAuthCodeModel()
    {
        return static::$authCodeModel;
    }

    /**
     * Set the auth code model.
     *
     * @param  mixed  $authCodeModel
     * @return static
     */
    public static function setAuthCodeModel($authCodeModel)
    {
        static::$authCodeModel = $authCodeModel;

        return new static;
    }

    /**
     * Get the token model.
     *
     * @return mixed
     */
    public static function getTokenModel()
    {
        return static::$tokenModel;
    }

    /**
     * Set the token model.
     *
     * @param  mixed  $tokenModel
     * @return static
     */
    public static function setTokenModel($tokenModel)
    {
        static::$tokenModel = $tokenModel;

        return new static;
    }
}
