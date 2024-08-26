<?php

namespace Laravel\Passport\Bridge;

use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * Create a new scope repository.
     */
    public function __construct(protected ClientRepository $clients)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeEntityByIdentifier(string $identifier): ?ScopeEntityInterface
    {
        return Passport::hasScope($identifier) ? new Scope($identifier) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function finalizeScopes(
        array $scopes,
        string $grantType,
        ClientEntityInterface $clientEntity,
        string|null $userIdentifier = null,
        ?string $authCodeId = null
    ): array {
        return collect($scopes)
            ->unless(in_array($grantType, ['password', 'personal_access', 'client_credentials']),
                fn ($scopes) => $scopes->reject(fn (Scope $scope) => $scope->getIdentifier() === '*')
            )
            ->filter(fn (Scope $scope) => Passport::hasScope($scope->getIdentifier()))
            ->when($this->clients->findActive($clientEntity->getIdentifier()),
                fn ($scopes, $client) => $scopes->filter(fn ($scope) => $client->hasScope($scope->getIdentifier()))
            )
            ->values()
            ->all();
    }
}
