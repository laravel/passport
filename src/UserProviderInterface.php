<?php


namespace Laravel\Passport;

interface UserProviderInterface
{
    public function getUserProvider();
    public function setUserProvider($userProvider);
}
