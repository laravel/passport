<?php

namespace Laravel\Passport\Bridge;

use Laravel\Passport\ResolvesInheritedScopes;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class Client implements ClientEntityInterface
{
    use ClientTrait, EntityTrait, ResolvesInheritedScopes;

    /**
     * Create a new client instance.
     */
    public function __construct(
        string $identifier,
        string $name,
        array $redirectUri,
        bool $isConfidential = false,
        public ?string $provider = null,
        protected ?array $scopes = null
    ) {
        $this->setIdentifier($identifier);

        $this->name = $name;
        $this->isConfidential = $isConfidential;
        $this->redirectUri = $redirectUri;
    }

    /**
     * Determine whether the client has the given scope.
     */
    public function hasScope(string $scope): bool
    {
        return is_null($this->scopes) || $this->scopeExists($scope, $this->scopes);
    }
}
