<?php

namespace Laravel\Passport;

use Illuminate\Database\Eloquent\Model;

class PersonalAccessClient extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'oauth_personal_access_clients';

    /**
     * The guarded attributes on the model.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The client relation model.
     *
     * @var string
     */
    protected static $clientModel = Client::class;

    /**
     * Get all of the authentication codes for the client.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function client()
    {
        return $this->belongsTo(static::$clientModel);
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
