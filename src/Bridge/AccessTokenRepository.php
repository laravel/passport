<?php

namespace Laravel\Passport\Bridge;

use DateTime;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Passport;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    use FormatsScopesForStorage;

    /**
     * The token repository instance.
     */
    protected TokenRepository $tokenRepository;

    /**
     * The event dispatcher instance.
     */
    protected Dispatcher $events;

    /**
     * Create a new repository instance.
     */
    public function __construct(TokenRepository $tokenRepository, Dispatcher $events)
    {
        $this->events = $events;
        $this->tokenRepository = $tokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewToken(
        ClientEntityInterface $clientEntity,
        array $scopes,
        mixed $userIdentifier = null
    ): AccessTokenEntityInterface {
        return new Passport::$accessTokenEntity($userIdentifier, $scopes, $clientEntity);
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $this->tokenRepository->create([
            'id' => $accessTokenEntity->getIdentifier(),
            'user_id' => $accessTokenEntity->getUserIdentifier(),
            'client_id' => $accessTokenEntity->getClient()->getIdentifier(),
            'scopes' => $this->scopesToArray($accessTokenEntity->getScopes()),
            'revoked' => false,
            'created_at' => new DateTime,
            'updated_at' => new DateTime,
            'expires_at' => $accessTokenEntity->getExpiryDateTime(),
        ]);

        $this->events->dispatch(new AccessTokenCreated(
            $accessTokenEntity->getIdentifier(),
            $accessTokenEntity->getUserIdentifier(),
            $accessTokenEntity->getClient()->getIdentifier()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAccessToken(string $tokenId): void
    {
        $this->tokenRepository->revokeAccessToken($tokenId);
    }

    /**
     * {@inheritdoc}
     */
    public function isAccessTokenRevoked(string $tokenId): bool
    {
        return $this->tokenRepository->isAccessTokenRevoked($tokenId);
    }
}
