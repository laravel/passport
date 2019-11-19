<?php


namespace Laravel\Passport;

interface UserProviderInterface
{
    public function getProvider();
    public function setProvider($provider);
}
