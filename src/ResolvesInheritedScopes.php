<?php

namespace Laravel\Passport;

trait ResolvesInheritedScopes
{
    /**
     * Resolve all possible scopes.
     *
     * @param  string  $scope
     * @return array
     */
    protected function resolveInheritedScopes($scope)
    {
        $parts = explode(':', $scope);

        $partsCount = count($parts);

        $scopes = [];

        for ($i = 1; $i <= $partsCount; $i++) {
            $scopes[] = implode(':', array_slice($parts, 0, $i));
        }

        return $scopes;
    }
}
