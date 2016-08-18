<?php

namespace Laravel\Passport\Bridge;

use Laravel\Passport\Client;
use Illuminate\Contracts\Hashing\Hasher;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    /**
     * The hasher implementation.
     *
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * Create a new repository instance.
     *
     * @param  \Illuminate\Contracts\Hashing\Hasher  $hasher
     * @return void
     */
    public function __construct(Hasher $hasher)
    {
        $this->hasher = $hasher;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity)
    {
        $client = Client::find($clientEntity->getIdentifier());

        if (! $client->personal_access_client) {
            return;
        }

        if (is_null($model = config('auth.providers.users.model'))) {
            return;
        }

        if (method_exists($model, 'findByUsername')) {
            $user = (new $model)->findByUsername($username);
        } else {
            $user = (new $model)->where('email', $username)->first();
        }

        if (! $this->hasher->check($password, $user->password)) {
            return;
        }

        return new User($user->id);
    }
}
