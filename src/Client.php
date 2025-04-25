<?php

namespace Laravel\Passport;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Laravel\Passport\Database\Factories\ClientFactory;

class Client extends Model
{
    /** @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Laravel\Passport\Database\Factories\ClientFactory> */
    use HasFactory;
    use ResolvesInheritedScopes;
    use HasUuids;

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
     * @var array<string, string>
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
     * Initialize the trait.
     */
    public function initializeHasUniqueStringIds(): void
    {
        $this->usesUniqueIds = Passport::$clientUuids;
    }

    /**
     * Get the user that the client belongs to.
     *
     * @deprecated Use owner()
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
     * Get the owner of the registered client.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Foundation\Auth\User, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo('owner');
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
     * Interact with the client's secret.
     */
    protected function secret(): Attribute
    {
        return Attribute::make(
            set: function (?string $value): ?string {
                $this->plainSecret = $value;

                return $this->castAttributeAsHashedString('secret', $value);
            },
        );
    }

    /**
     * Interact with the client's redirect URIs.
     */
    protected function redirectUris(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes): array => match (true) {
                isset($value) => $this->fromJson($value),
                ! empty($attributes['redirect']) => explode(',', $attributes['redirect']),
                default => [],
            },
        );
    }

    /**
     * Interact with the client's grant types.
     */
    protected function grantTypes(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): array => isset($value) ? $this->fromJson($value) : array_keys(array_filter([
                'authorization_code' => ! empty($this->redirect_uris),
                'client_credentials' => $this->confidential() && $this->firstParty(),
                'implicit' => ! empty($this->redirect_uris),
                'password' => $this->password_client,
                'personal_access' => $this->personal_access_client && $this->confidential(),
                'refresh_token' => true,
                'urn:ietf:params:oauth:grant-type:device_code' => true,
            ])),
        );
    }

    /**
     * Determine if the client is a "first party" client.
     */
    public function firstParty(): bool
    {
        if (array_key_exists('user_id', $this->attributes)) {
            return empty($this->user_id);
        }

        return empty($this->owner_id);
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
        return in_array($grantType, $this->grant_types);
    }

    /**
     * Determine whether the client has the given scope.
     */
    public function hasScope(string $scope): bool
    {
        return ! isset($this->attributes['scopes']) || $this->scopeExistsIn($scope, $this->scopes);
    }

    /**
     * Determine if the client is a confidential client.
     */
    public function confidential(): bool
    {
        return ! empty($this->secret);
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
