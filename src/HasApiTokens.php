<?php

namespace Laravel\Passport;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Passport\Contracts\ScopeAuthorizable;
use LogicException;

/**
 * @phpstan-require-implements \Laravel\Passport\Contracts\OAuthenticatable
 */
trait HasApiTokens
{
    /**
     * The current access token for the authentication user.
     */
    protected ?ScopeAuthorizable $accessToken = null;

    /**
     * Get all of the user's registered OAuth clients.
     *
     * @deprecated Use oauthApps()
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Laravel\Passport\Client, $this>
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Passport::clientModel(), 'user_id');
    }

    /**
     * Get all of the user's registered OAuth applications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<\Laravel\Passport\Client, $this>
     */
    public function oauthApps(): MorphMany
    {
        return $this->morphMany(Passport::clientModel(), 'owner');
    }

    /**
     * Get all of the access tokens for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Laravel\Passport\Token, $this>
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(Passport::tokenModel(), 'user_id')
            ->where(function (Builder $query): void {
                $query->whereHas('client', function (Builder $query): void {
                    $query->where(function (Builder $query): void {
                        $provider = $this->getProviderName();

                        $query->when($provider === config('auth.guards.api.provider'), function (Builder $query): void {
                            $query->orWhereNull('provider');
                        })->orWhere('provider', $provider);
                    });
                });
            });
    }

    /**
     * Get the access token currently associated with the user.
     */
    public function token(): ?ScopeAuthorizable
    {
        return $this->currentAccessToken();
    }

    /**
     * Get the access token currently associated with the user.
     */
    public function currentAccessToken(): ?ScopeAuthorizable
    {
        return $this->accessToken;
    }

    /**
     * Determine if the current API token has a given scope.
     */
    public function tokenCan(string $scope): bool
    {
        return $this->accessToken && $this->accessToken->can($scope);
    }

    /**
     * Determine if the current API token is missing a given scope.
     */
    public function tokenCant(string $scope): bool
    {
        return ! $this->tokenCan($scope);
    }

    /**
     * Create a new personal access token for the user.
     *
     * @param  string[]  $scopes
     */
    public function createToken(string $name, array $scopes = []): PersonalAccessTokenResult
    {
        return app(PersonalAccessTokenFactory::class)->make(
            $this->getAuthIdentifier(), $name, $scopes, $this->getProviderName()
        );
    }

    /**
     * Get the user provider name.
     *
     * @throws \LogicException
     */
    public function getProviderName(): string
    {
        $providers = collect(config('auth.guards'))->where('driver', 'passport')->pluck('provider')->all();

        foreach (config('auth.providers') as $provider => $config) {
            if (in_array($provider, $providers) && $config['driver'] === 'eloquent' && is_a($this, $config['model'])) {
                return $provider;
            }
        }

        throw new LogicException('Unable to determine authentication provider for this model from configuration.');
    }

    /**
     * Set the current access token for the user.
     */
    public function withAccessToken(?ScopeAuthorizable $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }
}
