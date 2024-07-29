<?php

namespace Laravel\Passport;

use Illuminate\Container\Container;

trait HasApiTokens
{
    /**
     * The current access token for the authentication user.
     *
     * @var \Laravel\Passport\AccessToken|\Laravel\Passport\TransientToken|null
     */
    protected $accessToken;

    /**
     * Get all of the user's registered OAuth clients.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clients()
    {
        return $this->hasMany(Passport::clientModel(), 'user_id');
    }

    /**
     * Get all of the access tokens for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tokens()
    {
        return $this->hasMany(Passport::tokenModel(), 'user_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get the current access token being used by the user.
     *
     * @return \Laravel\Passport\AccessToken|\Laravel\Passport\TransientToken|null
     */
    public function token()
    {
        return $this->accessToken;
    }

    /**
     * Get the access token currently associated with the user.
     *
     * @return \Laravel\Passport\AccessToken|\Laravel\Passport\TransientToken|null
     */
    public function currentAccessToken()
    {
        return $this->token();
    }

    /**
     * Determine if the current API token has a given scope.
     *
     * @param  string  $scope
     * @return bool
     */
    public function tokenCan($scope)
    {
        return $this->accessToken && $this->accessToken->can($scope);
    }

    /**
     * Create a new personal access token for the user.
     *
     * @param  string  $name
     * @param  array  $scopes
     * @return \Laravel\Passport\PersonalAccessTokenResult
     */
    public function createToken($name, array $scopes = [])
    {
        return Container::getInstance()->make(PersonalAccessTokenFactory::class)->make(
            $this->getAuthIdentifier(), $name, $scopes, $this->getProvider()
        );
    }

    /**
     * Get the user provider name.
     *
     * @return string|null
     */
    public function getProvider(): ?string
    {
        $providers = collect(config('auth.guards'))->where('driver', 'passport')->pluck('provider')->all();

        foreach (config('auth.providers') as $provider => $config) {
            if (in_array($provider, $providers)
                && (($config['driver'] === 'eloquent' && is_a($this, $config['model']))
                    || ($config['driver'] === 'database' && $config['table'] === $this->getTable()))) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * Set the current access token for the user.
     *
     * @param  \Laravel\Passport\AccessToken|\Laravel\Passport\TransientToken|null  $accessToken
     * @return $this
     */
    public function withAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }
}
