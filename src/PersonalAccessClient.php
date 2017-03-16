<?php

namespace Laravel\Passport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class PersonalAccessClient extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'personal_access_clients';

    /**
     * The guarded attributes on the model.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Creates a new PersonalAccessClient instance and sets its table name.
     *
     * @param  array  $attributes
     * @return \Laravel\Passport\PersonalAccessClient
     */
    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
        $this->table = config('passport.prefix').$this->table;
    }

    /**
     * Get all of the authentication codes for the client.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
