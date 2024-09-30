<?php

namespace Laravel\Passport;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class PassportUserProvider implements UserProvider
{
    /**
     * Create a new passport user provider.
     */
    public function __construct(
        protected UserProvider $provider,
        protected string $providerName
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveById($identifier): ?Authenticatable
    {
        return $this->provider->retrieveById($identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return $this->provider->retrieveByToken($identifier, $token);
    }

    /**
     * {@inheritdoc}
     */
    public function updateRememberToken(Authenticatable $user, $token): void
    {
        $this->provider->updateRememberToken($user, $token);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        return $this->provider->retrieveByCredentials($credentials);
    }

    /**
     * {@inheritdoc}
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * {@inheritdoc}
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        $this->provider->rehashPasswordIfRequired($user, $credentials, $force);
    }

    /**
     * Get the name of the user provider.
     */
    public function getProviderName(): string
    {
        return $this->providerName;
    }
}
