<?php

namespace Laravel\Passport\Bridge;

use RuntimeException;
use Illuminate\Hashing\HashManager;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    /**
     * The hasher implementation.
     *
     * @var \Illuminate\Hashing\HashManager
     */
    protected $hasher;

    /**
     * Create a new repository instance.
     *
     * @param  \Illuminate\Hashing\HashManager  $hasher
     * @return void
     */
    public function __construct(HashManager $hasher)
    {
        $this->hasher = $hasher->driver();
    }

    /**
     * {@inheritdoc}
     */
    public function getUserEntityByUserCredentials(
        $username, $password, $grantType,
        ClientEntityInterface $clientEntity)
    {
        $provider = config('auth.guards.api.provider');

        if (is_null($model = config('auth.providers.'.$provider.'.model'))) {
            throw new RuntimeException('Unable to determine authentication model from configuration.');
        }

        $user = method_exists($model, 'findForPassport')
            ? (new $model)->findForPassport($username)
            : (new $model)->where('email', $username)->first();

        if (! $user || ! $this->hasher->check($password, $user->getAuthPassword())) {
            return null;
        } elseif (method_exists($user, 'validateForPassportPasswordGrant')) {
            if (! $user->validateForPassportPasswordGrant($password)) {
                return null;
            }
        }

        return new User($user->getAuthIdentifier());
    }
}
