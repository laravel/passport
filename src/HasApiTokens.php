<?php

namespace Laravel\Passport;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

trait HasApiTokens
{
    /**
     * The current access token for the authentication user.
     */
    protected AccessToken|TransientToken|null $accessToken;

    /**
     * Get all of the user's registered OAuth clients.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Laravel\Passport\Client, $this>
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Passport::clientModel(), 'user_id');
    }

    /**
     * Get all of the access tokens for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Laravel\Passport\Token, $this>
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(Passport::tokenModel(), 'user_id')
            ->where(function (Builder $query) {
                $query->whereHas('client', function (Builder $query) {
                    $query->where(function (Builder $query) {
                        $provider = $this->getProvider();

                        $query->when($provider === config('auth.guards.api.provider'), function (Builder $query) {
                            $query->orWhereNull('provider');
                        })->orWhere('provider', $provider);
                    });
                });
            });
    }

    /**
     * Get the current access token being used by the user.
     */
    public function token(): AccessToken|TransientToken|null
    {
        return $this->accessToken;
    }

    /**
     * Get the access token currently associated with the user.
     */
    public function currentAccessToken(): AccessToken|TransientToken|null
    {
        return $this->token();
    }

    /**
     * Determine if the current API token has a given scope.
     */
    public function tokenCan(string $scope): bool
    {
        return $this->accessToken && $this->accessToken->can($scope);
    }

    /**
     * Create a new personal access token for the user.
     */
    public function createToken(string $name, array $scopes = []): PersonalAccessTokenResult
    {
        return app(PersonalAccessTokenFactory::class)->make(
            $this->getAuthIdentifier(), $name, $scopes, $this->getProvider()
        );
    }

    /**
     * Get the user provider name.
     *
     * @throws \LogicException
     */
    public function getProvider(): string
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
    public function withAccessToken(AccessToken|TransientToken|null $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }
}
