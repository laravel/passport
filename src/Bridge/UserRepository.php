<?php

namespace Laravel\Passport\Bridge;


use Illuminate\Auth\EloquentUserProvider;
use Laravel\Passport\Bridge\User;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;

class PassportUserRepository implements UserRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity)
    {
        $guard = config('auth.passport.guard'); // obtain current guard name from config
        if (is_null($guard)) {
            $guard = 'api';
        }
        $provider = config('auth.guards.' . $guard . '.provider');
        $userProvider = app('auth')->createUserProvider($provider);
        if (
            $userProvider instanceof EloquentUserProvider &&
            method_exists($model = $userProvider->getModel(), 'findForPassport')
        ) {
            $user = (new $model)->findForPassport($username);
        } else {
            $user = $userProvider->retrieveById($username);
        }
        if (!$user) {
            return;
        }

        if (method_exists($user, 'validateForPassportPasswordGrant')) {
            if (!$user->validateForPassportPasswordGrant($password)) {
                return;
            }
        } else {
            if (!$userProvider->validateCredentials($user, ['password' => $password])) {
                return;
            }
        }

        if ($user instanceof UserEntityInterface) {
            return $user;
        }

        return new User($user->getAuthIdentifier());
    }
}
