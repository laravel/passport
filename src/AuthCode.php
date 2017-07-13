<?php

namespace Laravel\Passport;

use Illuminate\Database\Eloquent\Model;
use Vinelab\NeoEloquent\Eloquent\Model as NeoEloquent;

class AuthCode extends NeoEloquent
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
     * Get the client that owns the authentication code.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function client()
    {
        return $this->hasMany(Client::class);
    }
}
