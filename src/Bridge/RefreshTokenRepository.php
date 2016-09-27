<?php

namespace Laravel\Passport\Bridge;

use Laravel\Passport\RefreshTokenRepository as RefreshTokenModelRepository;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * The refresh token model repository.
     *
     * @var \Laravel\Passport\RefreshTokenRepository
     */
    protected $refreshTokens;

    /**
     * Create a new repository instance.
     *
     * @param  \Laravel\Passport\RefreshTokenRepository  $refreshTokens
     * @return void
     */
    public function __construct(RefreshTokenModelRepository $refreshTokens)
    {
        $this->refreshTokens = $refreshTokens;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewRefreshToken()
    {
        return new RefreshToken;
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
    {
        $this->refreshTokens->create([
            'id' => $refreshTokenEntity->getIdentifier(),
            'access_token_id' => $refreshTokenEntity->getAccessToken()->getIdentifier(),
            'revoked' => false,
            'expires_at' => $refreshTokenEntity->getExpiryDateTime(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function revokeRefreshToken($tokenId)
    {
        $this->refreshTokens->revoke($tokenId);
    }

    /**
     * {@inheritdoc}
     */
    public function isRefreshTokenRevoked($tokenId)
    {
        return $this->refreshTokens->revoked($tokenId);
    }
}
