<?php

namespace Laravel\Passport\Bridge;

interface UserAwareInterface
{
    /**
     * Get the user associated with the entity
     *
     * @return User
     */
    public function getUser();

    /**
     * @param User $user
     *
     * @return void
     */
    public function setUser($user);
}
