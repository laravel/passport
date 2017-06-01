<?php

namespace Laravel\Passport\Bridge;

use Illuminate\Database\Connection;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Passport\Events\RefreshTokenCreated;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * The database connection.
     *
     * @var \Illuminate\Database\Connection
     */
    protected $database;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Events\Dispatcher
     */
    private $events;

    /**
     * The access token repository instance.
     *
     * @var \Laravel\Passport\Bridge\AccessTokenRepository
     */
    private $tokenRepository;

    /**
     * Create a new repository instance.
     *
     * @param  \Illuminate\Database\Connection  $database
     * @param  \Illuminate\Contracts\Events\Dispatcher $events
     * @param  \Laravel\Passport\Bridge\AccessTokenRepository $tokenRepository
     * @return void
     */
    public function __construct(Connection $database, Dispatcher $events, AccessTokenRepository $tokenRepository)
    {
        $this->events = $events;
        $this->database = $database;
        $this->tokenRepository = $tokenRepository;
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
        $this->database->table('oauth_refresh_tokens')->insert([
            'id' => $id = $refreshTokenEntity->getIdentifier(),
            'access_token_id' => $accessTokenId = $refreshTokenEntity->getAccessToken()->getIdentifier(),
            'revoked' => false,
            'expires_at' => $refreshTokenEntity->getExpiryDateTime(),
        ]);

        $this->events->fire(new RefreshTokenCreated($id, $accessTokenId));
    }

    /**
     * {@inheritdoc}
     */
    public function revokeRefreshToken($tokenId)
    {
        $this->database->table('oauth_refresh_tokens')
                    ->where('id', $tokenId)->update(['revoked' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function isRefreshTokenRevoked($tokenId)
    {
        $refreshToken = $this->database->table('oauth_refresh_tokens')
                    ->where('id', $tokenId)->first();
        if ($refreshToken === null) {
            // Refresh Token has been deleted from the database.
            return true;
        }
        if ($refreshToken->revoked) {
            // Refresh Token has been revoked.
            return true;
        }
        if ($this->tokenRepository->isAccessTokenRevoked($refreshToken->access_token_id)) {
            // Associated Access Token has been revoked.
            return true;
        }

        return false;
    }
}
