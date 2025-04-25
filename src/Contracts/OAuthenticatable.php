<?php

namespace Laravel\Passport\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Passport\PersonalAccessTokenResult;

interface OAuthenticatable extends Authenticatable
{
    /**
     * Get all the user's registered OAuth applications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<\Laravel\Passport\Client, \Illuminate\Foundation\Auth\User>
     */
    public function oauthApps(): MorphMany;

    /**
     * Get all the access tokens for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Laravel\Passport\Token, \Illuminate\Foundation\Auth\User>
     */
    public function tokens(): HasMany;

    /**
     * Determine if the current API token has a given scope.
     */
    public function tokenCan(string $scope): bool;

    /**
     * Determine if the current API token is missing a given scope.
     */
    public function tokenCant(string $scope): bool;

    /**
     * Create a new personal access token for the user.
     *
     * @param  string[]  $scopes
     */
    public function createToken(string $name, array $scopes = []): PersonalAccessTokenResult;

    /**
     * Get the access token currently associated with the user.
     */
    public function currentAccessToken(): ?ScopeAuthorizable;

    /**
     * Set the current access token for the user.
     */
    public function withAccessToken(?ScopeAuthorizable $accessToken): static;

    /**
     * Get the user provider name.
     */
    public function getProviderName(): string;
}
