<?php

namespace Laravel\Passport\Bridge;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;

class Client implements ClientEntityInterface
{
    use ClientTrait;

    /**
     * The client identifier.
     *
     * @var string
     */
    protected $identifier;

    /**
     * The client's provider.
     *
     * @var string
     */
    public $provider;

    /**
     * Create a new client instance.
     *
     * @param  string  $identifier
     * @param  string  $name
     * @param  string  $redirectUri
     * @param  bool  $isConfidential
     * @param  string|null  $provider
     * @return void
     */
    public function __construct($identifier, $name, $redirectUri, $isConfidential = false, $provider = null)
    {
        $this->setIdentifier((string) $identifier);

        $this->name = $name;
        $this->isConfidential = $isConfidential;
        $this->redirectUri = explode(',', $redirectUri);
        $this->provider = $provider;
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
     * @return void
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }
}
