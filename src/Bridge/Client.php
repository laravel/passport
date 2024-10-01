<?php

namespace Laravel\Passport\Bridge;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class Client implements ClientEntityInterface
{
    use ClientTrait, EntityTrait;

    /**
     * Create a new client instance.
     *
     * @param  non-empty-string  $identifier
     * @param  string[]  $redirectUri
     */
    public function __construct(
        string $identifier,
        string $name,
        array $redirectUri,
        bool $isConfidential = false,
        public ?string $provider = null,
        public ?array $grantTypes = null,
    ) {
        $this->setIdentifier($identifier);

        $this->name = $name;
        $this->isConfidential = $isConfidential;
        $this->redirectUri = $redirectUri;
    }

    /**
     * {@inheritdoc}
     */
    public function hasGrantType(string $grantType): bool
    {
        return is_null($this->grantTypes) || in_array($grantType, $this->grantTypes);
    }
}
