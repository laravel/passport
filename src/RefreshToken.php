<?php

namespace Laravel\Passport;

use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'oauth_refresh_tokens';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

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
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The access token relation model.
     *
     * @var string
     */
    protected static $tokenModel = Token::class;

    /**
     * Get the access token that the refresh token belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function accessToken()
    {
        return $this->belongsTo(static::$tokenModel);
    }

    /**
     * Revoke the token instance.
     *
     * @return void
     */
    public function revoke()
    {
        $this->forceFill(['revoked' => true])->save();
    }

    /**
     * Determine if the token is a transient JWT token.
     *
     * @return bool
     */
    public function transient()
    {
        return false;
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
