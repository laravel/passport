<?php

namespace Laravel\Passport\Bridge;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\ClientEntityInterface;

class Client implements ClientEntityInterface
{
    use ClientTrait, EntityTrait;

    /**
     * @var ScopeEntityInterface[]
     */
    protected $scopes = [];


    /**
     * Create a new client instance.
     *
     * @param  string  $identifier
     * @param  string  $name
     * @param  string  $redirectUri
     * @return void
     */
    public function __construct($identifier, $name, $redirectUri)
    {
        $this->setIdentifier((string) $identifier);

        $this->name = $name;
        $this->redirectUri = explode(',', $redirectUri);
    }

    /**
     * Associate a scope with the token.
     *
     * @param ScopeEntityInterface $scope
     */
    public function addScope(ScopeEntityInterface $scope)
    {
        $this->scopes[$scope->getIdentifier()] = $scope;
    }

    /**
     * Return an array of scopes associated with the token.
     *
     * @return ScopeEntityInterface[]
     */
    public function getScopes()
    {
        return array_values($this->scopes);
    }

    /**
     * Determine if the given scope has been defined.
     *
     * @param string $id
     * @return bool
     */
    public function hasScope($id)
    {
        return $id === '*' || array_key_exists($id, $this->scopes);
    }

}
