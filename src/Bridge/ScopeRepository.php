<?php

namespace Laravel\Passport\Bridge;

use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getScopeEntityByIdentifier($identifier)
    {
        if (Passport::hasScope($identifier)) {
            return new Scope($identifier);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finalizeScopes(
        array $scopes, $grantType,
        ClientEntityInterface $clientEntity, $userIdentifier = null)
    {
        $client_repository = new ClientRepository();

        if ($grantType !== 'password') {
            $scopes = collect($scopes)->reject(function ($scope) {
                return trim($scope->getIdentifier()) === '*';
            })->values()->all();
        }

        $client_id = collect($clientEntity)->values()->last();

        //remove scopes that are not assigned to this client
        $scopes = collect($scopes)->reject(function ($scope) use ($client_id, $client_repository) {
                return !$client_repository->hasScope($client_id, trim($scope->getIdentifier()));
            })->values()->all();

        return collect($scopes)->filter(function ($scope) {
            return Passport::hasScope($scope->getIdentifier());
        })->values()->all();
    }
}
