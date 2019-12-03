<?php

namespace Laravel\Passport\Bridge;

use Laravel\Passport\UserProviderInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;

class Client implements ClientEntityInterface, UserProviderInterface
{
    use ClientTrait;

    /**
     * The client identifier.
     *
     * @var string
     */
    protected $identifier;

    /**
     * @var string|null
     */
    protected $userProvider;

    /**
     * Create a new client instance.
     *
     * @param  string  $identifier
     * @param  string  $name
     * @param  string  $redirectUri
     * @param  string  $userProvider
     * @param  bool  $isConfidential
     * @return void
     */
    public function __construct($identifier, $name, $redirectUri, $isConfidential = false, $userProvider = null)
    {
        $this->setIdentifier((string) $identifier);

        $this->name = $name;
        $this->isConfidential = $isConfidential;
        $this->redirectUri = explode(',', $redirectUri);
        $this->userProvider = $userProvider;
    }

    /**
     * Get the client's identifier.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return (string) $this->identifier;
    }

    /**
     * Set the client's identifier.
     *
     * @param  string  $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    public function getUserProvider()
    {
        return $this->userProvider;
    }

    public function setUserProvider($userProvider)
    {
        $this->userProvider = $userProvider;
    }
}
