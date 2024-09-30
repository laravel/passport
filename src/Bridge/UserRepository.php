<?php

namespace Laravel\Passport\Bridge;

use Illuminate\Contracts\Hashing\Hasher;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use RuntimeException;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Create a new repository instance.
     */
    public function __construct(
        protected Hasher $hasher
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getUserEntityByUserCredentials(
        string $username,
        string $password,
        string $grantType,
        ClientEntityInterface $clientEntity
    ): ?UserEntityInterface {
        $provider = $clientEntity->provider ?: config('auth.guards.api.provider');

        if (is_null($model = config('auth.providers.'.$provider.'.model'))) {
            throw new RuntimeException('Unable to determine authentication model from configuration.');
        }

        if (method_exists($model, 'findAndValidateForPassport')) {
            $user = (new $model)->findAndValidateForPassport($username, $password);

            return $user ? new User($user->getAuthIdentifier()) : null;
        }

        $user = method_exists($model, 'findForPassport')
            ? (new $model)->findForPassport($username)
            : (new $model)->where('email', $username)->first();

        if (! $user) {
            return null;
        }

        if (method_exists($user, 'validateForPassportPasswordGrant')) {
            return $user->validateForPassportPasswordGrant($password)
                ? new User($user->getAuthIdentifier())
                : null;
        }

        return $this->hasher->check($password, $user->getAuthPassword())
            ? new User($user->getAuthIdentifier())
            : null;
    }
}
