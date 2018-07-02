<?php

namespace Laravel\Passport\Bridge;

use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * @var ClientRepository 
     */
    protected $clients;

    /**
     * @param ClientRepository $clients
     */
    public function __construct(ClientRepository $clients)
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
        if (! in_array($grantType, ['password', 'personal_access'])) {
            $scopes = collect($scopes)->reject(function ($scope) {
                return trim($scope->getIdentifier()) === '*';
            })->values()->all();
        }

        $client = $this->clients->findActive($clientEntity->getIdentifier());

        return collect($scopes)->filter(function ($scope) use($client) {
            return Passport::hasScope($scope->getIdentifier())
                && $client->hasScope($scope->getIdentifier());
        })->values()->all();
    }
}
