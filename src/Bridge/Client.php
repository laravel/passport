<?php

namespace Laravel\Passport\Bridge;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class Client implements ClientEntityInterface
{
    use ClientTrait, EntityTrait;

    /**
     * The client's provider.
     */
    public ?string $provider;

    /**
     * Create a new client instance.
     */
    public function __construct(
        string $identifier,
        ?string $name = null,
        ?string $redirectUri = null,
        bool $isConfidential = false,
        ?string $provider = null
    ) {
        $this->setIdentifier($identifier);

        if (! is_null($name)) {
            $this->name = $name;
        }
        
        if (! is_null($redirectUri)) {
            $this->redirectUri = explode(',', $redirectUri);
        }

        $this->isConfidential = $isConfidential;
        $this->provider = $provider;
    }
}
