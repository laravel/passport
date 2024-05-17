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
        string $name,
        string $redirectUri,
        bool $isConfidential = false,
        ?string $provider = null
    ) {
        $this->setIdentifier($identifier);

        $this->name = $name;
        $this->isConfidential = $isConfidential;
        $this->redirectUri = explode(',', $redirectUri);
        $this->provider = $provider;
    }
}
