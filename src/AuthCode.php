<?php

namespace Laravel\Passport;

use Illuminate\Database\Eloquent\Model;

class AuthCode extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'oauth_auth_codes';

    /**
     * The guarded attributes on the model.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'revoked' => 'bool',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'expires_at',
    ];

    /**
     * The client relation model.
     *
     * @var string
     */
    protected static $clientModel = Client::class;

    /**
     * Get the client that owns the authentication code.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function client()
    {
        return $this->hasMany(static::$clientModel);
    }

    /**
     * Get the client model.
     *
     * @return mixed
     */
    public static function getClientModel()
    {
        return static::$clientModel;
    }

    /**
     * Set the client model.
     *
     * @param  mixed  $clientModel
     * @return static
     */
    public static function setClientModel($clientModel)
    {
        static::$clientModel = $clientModel;

        return new static;
    }
}
