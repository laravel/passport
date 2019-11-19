<?php


namespace Laravel\Passport;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserProviderResolver
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var AuthManager
     */
    private $manager;

    public function __construct(Config $config, AuthManager $manager)
    {
        $this->config = $config;
        $this->manager = $manager;
    }

    public function resolve($providerName)
    {
        return $this->manager->createUserProvider($providerName);
    }

    public function getProvider($providerName)
    {
        return is_null($providerName) ? $this->config->get('auth.guards.api.provider') : $providerName;
    }
}
