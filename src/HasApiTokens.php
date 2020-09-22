<?php

namespace Laravel\Passport;

use Illuminate\Container\Container;

trait HasApiTokens
{
    /**
     * The current access token for the authentication user.
     *
     * @var \Laravel\Passport\Token
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
        $tokenModel = Passport::token();
        $clientModel = Passport::client();

        return $this->hasMany(Passport::tokenModel(), 'user_id')
            ->join(
                $clientModel->getTable(),
                $tokenModel->getTable() . '.client_id',
                '=',
                $clientModel->getTable() . '.' . $clientModel->getKeyName()
            )
            ->where($clientModel->getTable() . '.provider', $this->getProvider())
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the current access token being used by the user.
     *
     * @return \Laravel\Passport\Token|null
     */
    public function token()
    {
        return $this->accessToken;
    }

    /**
     * Determine if the current API token has a given scope.
     *
     * @param  string  $scope
     * @return bool
     */
    public function tokenCan($scope)
    {
        return $this->accessToken ? $this->accessToken->can($scope) : false;
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
        $provider = $this->getProvider();

        return Container::getInstance()->make(PersonalAccessTokenFactory::class)->make(
            $this->getKey(), $name, $scopes, $provider
        );
    }

    /**
     * Set the current access token for the user.
     *
     * @param  \Laravel\Passport\Token  $accessToken
     * @return $this
     */
    public function withAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    protected function getProvider() {
        $providers = config('auth.providers');

        foreach ($providers as $name => $config) {
            if($config['driver'] == 'eloquent' && is_a($this, $config['model'])) {
                return $name;
            }

            if($config['driver'] == 'database' && $this->getTable() == $config['table']) {
                return $name;
            }
        }

        return null;
    }
}
