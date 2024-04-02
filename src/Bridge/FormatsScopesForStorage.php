<?php

namespace Laravel\Passport\Bridge;

trait FormatsScopesForStorage
{
    /**
     * Format the given scopes for storage.
     *
     * @param  \League\OAuth2\Server\Entities\ScopeEntityInterface[]  $scopes
     */
    public function formatScopesForStorage(array $scopes): string
    {
        return json_encode($this->scopesToArray($scopes));
    }

    /**
     * Get an array of scope identifiers for storage.
     *
     * @param  \League\OAuth2\Server\Entities\ScopeEntityInterface[]  $scopes
     * @return string[]
     */
    public function scopesToArray(array $scopes): array
    {
        return array_map(function ($scope) {
            return $scope->getIdentifier();
        }, $scopes);
    }
}
