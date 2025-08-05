<?php

namespace Laravel\Passport\Bridge;

use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Events\AccessTokenRevoked;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * Create a new repository instance.
     */
    public function __construct(
        protected Dispatcher $events
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getNewToken(
        ClientEntityInterface $clientEntity,
        array $scopes,
        ?string $userIdentifier = null
    ): AccessTokenEntityInterface {
        return new Passport::$accessTokenEntity($userIdentifier, $scopes, $clientEntity);
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        Passport::token()->forceFill([
            'id' => $id = $accessTokenEntity->getIdentifier(),
            'user_id' => $userId = $accessTokenEntity->getUserIdentifier(),
            'client_id' => $clientId = $accessTokenEntity->getClient()->getIdentifier(),
            'scopes' => $accessTokenEntity->getScopes(),
            'revoked' => false,
            'expires_at' => $accessTokenEntity->getExpiryDateTime(),
        ])->save();

        $this->events->dispatch(new AccessTokenCreated($id, $userId, $clientId));
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAccessToken(string $tokenId): void
    {
        if (Passport::token()->newQuery()->whereKey($tokenId)->update(['revoked' => true])) {
            $this->events->dispatch(new AccessTokenRevoked($tokenId));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAccessTokenRevoked(string $tokenId): bool
    {
        return Passport::token()->newQuery()->whereKey($tokenId)->where('revoked', false)->doesntExist();
    }
}
