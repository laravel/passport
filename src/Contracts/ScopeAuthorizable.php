<?php

namespace Laravel\Passport\Contracts;

interface ScopeAuthorizable
{
    /**
     * Determine if the token has a given scope.
     */
    public function can(string $scope): bool;

    /**
     * Determine if the token is missing a given scope.
     */
    public function cant(string $scope): bool;
}
