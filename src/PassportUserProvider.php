<?php

namespace Laravel\Passport;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class PassportUserProvider implements UserProvider
{
    /**
     * The Application UserProvider instance.
     *
     * @var \Illuminate\Contracts\Auth\UserProvider
     */
    protected $provider;

    /**
     * The Application UserProvider name.
     *
     * @var string
     */
    protected $providerName;

    /**
     * Create a new passport user provider.
     *
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @param  string  $providerName
     * @return void
     */
    public function __construct(UserProvider $provider, $providerName)
    {
        $this->provider = $provider;
        $this->providerName = $providerName;
    }

    /**
     * Get the UserProvider name.
     *
     * @return string
     */
    public function getProviderName()
    {
        return $this->providerName;
    }

    /**
     * {@inheritDoc}
     */
    public function retrieveById($identifier)
    {
        return $this->provider->retrieveById($identifier);
    }

    /**
     * {@inheritDoc}
     */
    public function retrieveByToken($identifier, $token)
    {
        return $this->provider->retrieveByToken($identifier, $token);
    }

    /**
     * {@inheritDoc}
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $this->provider->updateRememberToken($user, $token);
    }

    /**
     * {@inheritDoc}
     */
    public function retrieveByCredentials(array $credentials)
    {
        return $this->provider->retrieveByCredentials($credentials);
    }

    /**
     * {@inheritDoc}
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return $this->provider->validateCredentials($user, $credentials);
    }
}
