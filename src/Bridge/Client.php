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
}
