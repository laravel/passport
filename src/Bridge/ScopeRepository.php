<?php

namespace Laravel\Passport\Bridge;

use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * The client repository.
     *
     * @var \Laravel\Passport\ClientRepository|null
     */
    protected ?ClientRepository $clients;

    /**
     * Create a new scope repository.
     *
     * @param  \Laravel\Passport\ClientRepository|null  $clients
     * @return void
     */
    public function __construct(?ClientRepository $clients = null)
    {
        $this->clients = $clients;
    }

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
        if (! in_array($grantType, ['password', 'personal_access', 'client_credentials'])) {
            $scopes = collect($scopes)->reject(function ($scope) {
                return trim($scope->getIdentifier()) === '*';
            })->values()->all();
        }

        $client = $this->clients?->findActive($clientEntity->getIdentifier());

        return collect($scopes)->filter(function ($scope) {
            return Passport::hasScope($scope->getIdentifier());
        })->when($client, function ($scopes, $client) {
            return $scopes->filter(fn ($scope) => $client->hasScope($scope->getIdentifier()));
        })->values()->all();
    }
}
