<?php

namespace Laravel\Passport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Token extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'oauth_access_tokens';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The guarded attributes on the model.
     *
     * @var array<string>|bool
     */
    protected $guarded = false;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scopes' => 'array',
        'revoked' => 'bool',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the client that the token belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Laravel\Passport\Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Passport::clientModel());
    }

    /**
     * Get the refresh token associated with the token.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\Laravel\Passport\RefreshToken, $this>
     */
    public function refreshToken(): HasOne
    {
        return $this->hasOne(Passport::refreshTokenModel(), 'access_token_id');
    }

    /**
     * Get the user that the token belongs to.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Illuminate\Foundation\Auth\User, $this>
     */
    public function user(): BelongsTo
    {
        $provider = $this->client->provider ?: config('auth.guards.api.provider');

        $model = config('auth.providers.'.$provider.'.model');

        return $this->belongsTo($model, 'user_id', (new $model)->getAuthIdentifierName());
    }

    /**
     * Revoke the token instance.
     */
    public function revoke(): bool
    {
        return $this->forceFill(['revoked' => true])->save();
    }

    /**
     * Get the current connection name for the model.
     */
    public function getConnectionName(): ?string
    {
        return $this->connection ?? config('passport.connection');
    }
}
