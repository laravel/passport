<?php

namespace Laravel\Passport;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Database\Factories\ClientFactory;

class Client extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Laravel\Passport\Database\Factories\ClientFactory> */
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
     * @var array<string>|bool
     */
    protected $guarded = false;

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array<string>
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, \Illuminate\Contracts\Database\Eloquent\Castable|string>
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
     */
    public ?string $plainSecret = null;

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->usesUniqueIds = Passport::$clientUuids;
    }

    /**
     * Get the user that the client belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Illuminate\Foundation\Auth\User, $this>
     */
    public function user(): BelongsTo
    {
        $provider = $this->provider ?: config('auth.guards.api.provider');

        return $this->belongsTo(
            config("auth.providers.$provider.model")
        );
    }

    /**
     * Get all of the authentication codes for the client.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Laravel\Passport\AuthCode, $this>
     */
    public function authCodes(): HasMany
    {
        return $this->hasMany(Passport::authCodeModel(), 'client_id');
    }

    /**
     * Get all of the tokens that belong to the client.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Laravel\Passport\Token, $this>
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(Passport::tokenModel(), 'client_id');
    }

    /**
     * The temporary non-hashed client secret.
     *
     * This is only available once during the request that created the client.
     */
    public function getPlainSecretAttribute(): ?string
    {
        return $this->plainSecret;
    }

    /**
     * Set the value of the secret attribute.
     */
    public function setSecretAttribute(?string $value): void
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
     */
    public function hasGrantType(string $grantType): bool
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
     */
    public function confidential(): bool
    {
        return ! empty($this->secret);
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array<string>
     */
    public function uniqueIds(): array
    {
        return $this->usesUniqueIds ? [$this->getKeyName()] : [];
    }

    /**
     * Generate a new key for the model.
     */
    public function newUniqueId(): ?string
    {
        return $this->usesUniqueIds ? (string) Str::orderedUuid() : null;
    }

    /**
     * Get the auto-incrementing key type.
     */
    public function getKeyType(): string
    {
        return $this->usesUniqueIds ? 'string' : $this->keyType;
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     */
    public function getIncrementing(): bool
    {
        return $this->usesUniqueIds ? false : $this->incrementing;
    }

    /**
     * Get the current connection name for the model.
     */
    public function getConnectionName(): ?string
    {
        return $this->connection ?? config('passport.connection');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Laravel\Passport\Database\Factories\ClientFactory
     */
    protected static function newFactory(): Factory
    {
        return ClientFactory::new();
    }
}
