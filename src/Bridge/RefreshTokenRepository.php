<?php

namespace Laravel\Passport\Bridge;

use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Passport\Events\RefreshTokenCreated;
use Laravel\Passport\RefreshTokenRepository as PassportRefreshTokenRepository;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * The refresh token repository instance.
     */
    protected PassportRefreshTokenRepository $refreshTokenRepository;

    /**
     * The event dispatcher instance.
     */
    protected Dispatcher $events;

    /**
     * Create a new repository instance.
     */
    public function __construct(PassportRefreshTokenRepository $refreshTokenRepository, Dispatcher $events)
    {
        $this->events = $events;
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        return new RefreshToken;
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        $this->refreshTokenRepository->create([
            'id' => $id = $refreshTokenEntity->getIdentifier(),
            'access_token_id' => $accessTokenId = $refreshTokenEntity->getAccessToken()->getIdentifier(),
            'revoked' => false,
            'expires_at' => $refreshTokenEntity->getExpiryDateTime(),
        ]);

        $this->events->dispatch(new RefreshTokenCreated($id, $accessTokenId));
    }

    /**
     * {@inheritdoc}
     */
    public function revokeRefreshToken(string $tokenId): void
    {
        $this->refreshTokenRepository->revokeRefreshToken($tokenId);
    }

    /**
     * {@inheritdoc}
     */
    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        return $this->refreshTokenRepository->isRefreshTokenRevoked($tokenId);
    }
}
