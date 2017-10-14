<?php

namespace Laravel\Passport\Bridge;

use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\ClientEntityInterface;

class Client implements ClientEntityInterface
{
    use ClientTrait, EntityTrait;
    protected $guard;

    /**
     * Create a new client instance.
     *
     * @param  string  $identifier
     * @param  string  $name
     * @param  string  $guard
     * @param  string  $redirectUri
     * @return void
     */
    public function __construct($identifier, $name, $guard, $redirectUri)
    {
        $this->setIdentifier($identifier);
        $this->guard = $guard;
        $this->name = $name;
        $this->redirectUri = explode(',', $redirectUri);
    }

    /**
     * Returns the registered guard name (as a string).
     * @return string
     */
    public function getGuard(){
        return $this->guard;
    }
}
