<?php

namespace Laravel\Passport\Bridge;

use League\OAuth2\Server\Entities\Traits\EntityTrait;
use OpenIDConnectServer\Entities\ClaimSetInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;

class User implements ClaimSetInterface, UserEntityInterface
{
    use EntityTrait;

    /**
     * Create a new user instance.
     *
     * @param  string|int  $identifier
     * @return void
     */
    public function __construct($identifier)
    {
        $this->setIdentifier($identifier);
    }

    public function getClaims()
    {
        $user = \App\User::find($this->identifier);

        return [
            'name' => $user->name,
            'family_name' => $user->lastname,
            'middle_name' => $user->patronymic,
            'nickname' => '',
            'email' => $user->email,
            'email_verified' => false,
            'address' => $user->address,
            'phone_number' => $user->phone,
            'phone_number_verified' => false,
            'preferred_username' => '',
            'profile' => '',
            'picture' => $user->picture,
            'website' => '',
            'gender' => $user->gender,
            'birthdate' => $user->birthday_date,
            'zoneinfo' => '',
            'locale' => '',
            'updated_at' => $user->updated_at,
            'roles' => $user->roles
        ];
    }
}
