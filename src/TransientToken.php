<?php

namespace Laravel\Passport;

class TransientToken
{
    /**
     * Determine if the token has a given scope.
     */
    public function can(string $scope): bool
    {
        return true;
    }

    /**
     * Determine if the token is missing a given scope.
     */
    public function cant(string $scope): bool
    {
        return false;
    }

    /**
     * Determine if the token is a transient JWT token.
     */
    public function transient(): bool
    {
        return true;
    }
}
