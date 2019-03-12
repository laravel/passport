<?php

namespace Laravel\Passport\Bridge;

use Illuminate\Auth\EloquentUserProvider;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{

    /**
     *
     * {@inheritdoc}
     */
    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity)
    {
        $auth = app('auth');
        $guard = $auth->getDefaultDriver();
        $provider = config('auth.guards.' . $guard . '.provider');
        $userProvider = $auth->createUserProvider($provider);

        if ($userProvider instanceof EloquentUserProvider) {
            $model = $userProvider->getModel();
            if (method_exists($model, 'findForPassport')) {
                $user = (new $model())->findForPassport($username);
            } else {
                $user = (new $model())->where('email', $username)->first();
            }
        } else {
            $user = $userProvider->retrieveById($username);
        }

        /*
        if ($userProvider instanceof EloquentUserProvider &&
            method_exists($model = $userProvider->getModel(), 'findForPassport')) {
            $user = (new $model)->findForPassport($username);
        } else {
            $user = $userProvider->retrieveById($username);
        }
        */

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

        return new User($user->getAuthIdentifier());
    }
}
