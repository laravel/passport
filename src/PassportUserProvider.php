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
     * Retrieve a user by their unique identifier.
     *
     * @param  string|int  $identifier
     * @return \Laravel\Passport\Contracts\OAuthenticatable|null
     */
    public function retrieveById($identifier): ?Authenticatable
    {
        return $this->provider->retrieveById($identifier);
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  string|int  $identifier
     * @param  string  $token
     * @return \Laravel\Passport\Contracts\OAuthenticatable|null
     */
    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?Authenticatable
    {
        return $this->provider->retrieveByToken($identifier, $token);
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Laravel\Passport\Contracts\OAuthenticatable  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, #[\SensitiveParameter] $token): void
    {
        $this->provider->updateRememberToken($user, $token);
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Laravel\Passport\Contracts\OAuthenticatable|null
     */
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        return $this->provider->retrieveByCredentials($credentials);
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Laravel\Passport\Contracts\OAuthenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, #[\SensitiveParameter] array $credentials): bool
    {
        return $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Rehash the user's password if required and supported.
     *
     * @param  \Laravel\Passport\Contracts\OAuthenticatable  $user
     * @param  array  $credentials
     * @param  bool  $force
     * @return void
     */
    public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false): void
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
