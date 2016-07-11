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
     * Get the client that owns the authentication code.
     */
    public function client()
    {
        return $this->hasMany(Client::class);
    }
}
