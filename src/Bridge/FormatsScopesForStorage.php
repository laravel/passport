<?php

namespace Laravel\Passport\Bridge;

trait FormatsScopesForStorage
{
    /**
     * Format the given scopes for storage.
     *
     * @param  array  $scopes
     * @return string
     */
    public function formatScopesForStorage(array $scopes)
    {
        return json_encode(array_map(function ($scope) {
            return $scope->getIdentifier();
        }, $scopes));
    }
}
