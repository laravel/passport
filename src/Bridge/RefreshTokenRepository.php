<?php

namespace Laravel\Passport\Bridge;

use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Passport\Events\RefreshTokenCreated;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
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
    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        return new RefreshToken;
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        Passport::refreshToken()->forceFill([
            'id' => $id = $refreshTokenEntity->getIdentifier(),
            'access_token_id' => $accessTokenId = $refreshTokenEntity->getAccessToken()->getIdentifier(),
            'revoked' => false,
            'expires_at' => $refreshTokenEntity->getExpiryDateTime(),
        ])->save();

        $this->events->dispatch(new RefreshTokenCreated($id, $accessTokenId));
    }

    /**
     * {@inheritdoc}
     */
    public function revokeRefreshToken(string $tokenId): void
    {
        Passport::refreshToken()->newQuery()->whereKey($tokenId)->update(['revoked' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        return Passport::refreshToken()->newQuery()->whereKey($tokenId)->where('revoked', false)->doesntExist();
    }
}
