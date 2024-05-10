<?php

namespace Laravel\Passport\Bridge;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Auth;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use RuntimeException;

class UserRepository implements UserRepositoryInterface
{
    /**
     * The hasher implementation.
     *
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * Indicates if passwords should be rehashed on login if needed.
     *
     * @var bool
     */
    protected $rehashOnLogin;

    /**
     * Create a new repository instance.
     *
     * @param  \Illuminate\Contracts\Hashing\Hasher  $hasher
     * @param  bool  $rehashOnLogin
     *
     * @return void
     */
    public function __construct(Hasher $hasher, bool $rehashOnLogin = true)
    {
        $this->hasher = $hasher;
        $this->rehashOnLogin = $rehashOnLogin;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity)
    {
        $provider = $clientEntity->provider ?: config('auth.guards.api.provider');

        if (is_null($model = config('auth.providers.'.$provider.'.model'))) {
            throw new RuntimeException('Unable to determine authentication model from configuration.');
        }

        if (method_exists($model, 'findAndValidateForPassport')) {
            $user = (new $model)->findAndValidateForPassport($username, $password);

            if (! $user) {
                return;
            }

            return new User($user->getAuthIdentifier());
        }

        if (method_exists($model, 'findForPassport')) {
            $user = (new $model)->findForPassport($username);
        } else {
            $user = (new $model)->where('email', $username)->first();
        }

        if (! $user) {
            return;
        } elseif (method_exists($user, 'validateForPassportPasswordGrant')) {
            if (! $user->validateForPassportPasswordGrant($password)) {
                return;
            }
        } elseif (! $this->hasher->check($password, $user->getAuthPassword())) {
            return;
        }

        if ($this->rehashOnLogin && method_exists(Auth::createUserProvider($provider), 'rehashPasswordIfRequired')) {
            Auth::createUserProvider($provider)->rehashPasswordIfRequired($user, ['password' => $password]);
        }

        return new User($user->getAuthIdentifier());
    }
}
