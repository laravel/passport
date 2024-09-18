<?php

namespace Laravel\Passport;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Database\Factories\ClientFactory;

class Client extends Model
{
    use HasFactory;
    use ResolvesInheritedScopes;

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
        'grant_types' => 'array',
        'scopes' => 'array',
        'redirect_uris' => 'array',
        'personal_access_client' => 'bool',
        'password_client' => 'bool',
        'revoked' => 'bool',
    ];

    /**
     * The temporary plain-text client secret.
     *
     * This is only available during the request that created the client.
     *
     * @var string|null
     */
    public $plainSecret;

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->usesUniqueIds = Passport::$clientUuids;
    }

    /**
     * Get the user that the client belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        $provider = $this->provider ?: config('auth.guards.api.provider');

        return $this->belongsTo(
            config("auth.providers.{$provider}.model")
        );
    }

    /**
     * Get all of the authentication codes for the client.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function authCodes()
    {
        return $this->hasMany(Passport::authCodeModel(), 'client_id');
    }

    /**
     * Get all of the tokens that belong to the client.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tokens()
    {
        return $this->hasMany(Passport::tokenModel(), 'client_id');
    }

    /**
     * The temporary non-hashed client secret.
     *
     * This is only available once during the request that created the client.
     *
     * @return string|null
     */
    public function getPlainSecretAttribute()
    {
        return $this->plainSecret;
    }

    /**
     * Set the value of the secret attribute.
     *
     * @param  string|null  $value
     * @return void
     */
    public function setSecretAttribute($value)
    {
        $this->plainSecret = $value;

        $this->attributes['secret'] = is_null($value) ? $value : Hash::make($value);
    }

    /**
     * Get the client's redirect URIs.
     */
    protected function redirectUris(): Attribute
    {
        return Attribute::make(
            get: function (?string $value, array $attributes) {
                if (isset($value)) {
                    return $this->fromJson($value);
                }

                return empty($attributes['redirect']) ? [] : explode(',', $attributes['redirect']);
            },
        );
    }

    /**
     * Determine if the client is a "first party" client.
     */
    public function firstParty(): bool
    {
        return empty($this->user_id);
    }

    /**
     * Determine if the client should skip the authorization prompt.
     *
     * @param  \Laravel\Passport\Scope[]  $scopes
     */
    public function skipsAuthorization(Authenticatable $user, array $scopes): bool
    {
        return false;
    }

    /**
     * Determine if the client has the given grant type.
     *
     * @param  string  $grantType
     * @return bool
     */
    public function hasGrantType($grantType)
    {
        if (isset($this->attributes['grant_types']) && is_array($this->grant_types)) {
            return in_array($grantType, $this->grant_types);
        }

        return match ($grantType) {
            'authorization_code' => ! $this->personal_access_client && ! $this->password_client,
            'personal_access' => $this->personal_access_client && $this->confidential(),
            'password' => $this->password_client,
            'client_credentials' => $this->confidential(),
            default => true,
        };
    }

    /**
     * Determine whether the client has the given scope.
     */
    public function hasScope(string $scope): bool
    {
        return ! isset($this->attributes['scopes']) || $this->scopeExists($scope, $this->scopes);
    }

    /**
     * Determine if the client is a confidential client.
     *
     * @return bool
     */
    public function confidential()
    {
        return ! empty($this->secret);
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    public function uniqueIds()
    {
        return $this->usesUniqueIds ? [$this->getKeyName()] : [];
    }

    /**
     * Generate a new key for the model.
     *
     * @return string
     */
    public function newUniqueId()
    {
        return $this->usesUniqueIds ? (string) Str::orderedUuid() : null;
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType()
    {
        return $this->usesUniqueIds ? 'string' : $this->keyType;
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return $this->usesUniqueIds ? false : $this->incrementing;
    }

    /**
     * Get the current connection name for the model.
     *
     * @return string|null
     */
    public function getConnectionName()
    {
        return $this->connection ?? config('passport.connection');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return ClientFactory::new();
    }
}
