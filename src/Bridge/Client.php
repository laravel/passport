<?php

namespace Laravel\Passport\Bridge;

use League\OAuth2\Server\Entities\Traits\{ ClientTrait, EntityTrait };
use League\OAuth2\Server\Entities\ClientEntityInterface;

class Client implements ClientEntityInterface
{
    use ClientTrait, EntityTrait;

    /**
     * Create a new client instance.
     *
     * @param  string  $identifier
     * @param  string  $name
     * @param  string  $redirectUri
     * @return void
     */
    public function __construct(string $identifier, string $name, string $redirectUri)
    {
        $this->setIdentifier((string) $identifier);

        $this->name = $name;
        $this->redirectUri = explode(',', $redirectUri);
    }
}
