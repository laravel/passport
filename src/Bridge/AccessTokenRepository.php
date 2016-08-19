<?php

namespace Laravel\Passport\Bridge;

use DateTime;
use Illuminate\Database\Connection;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    use FormatsScopesForStorage;

    /**
     * The database connection.
     *
     * @var \Illuminate\Database\Connection
     */
    protected $database;

    /**
     * Create a new repository instance.
     *
     * @param  \Illuminate\Database\Connection  $database
     * @return void
     */
    public function __construct(Connection $database)
    {
        $this->database = $database;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
    {
        return new AccessToken($userIdentifier, $scopes);
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
    {
        $this->database->table('oauth_access_tokens')->insert([
            'id' => $accessTokenEntity->getIdentifier(),
            'user_id' => $accessTokenEntity->getUserIdentifier(),
            'client_id' => $accessTokenEntity->getClient()->getIdentifier(),
            'scopes' => $this->formatScopesForStorage($accessTokenEntity->getScopes()),
            'revoked' => false,
            'created_at' => new DateTime,
            'updated_at' => new DateTime,
            'expires_at' => $accessTokenEntity->getExpiryDateTime(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAccessToken($tokenId)
    {
        $this->database->table('oauth_access_tokens')
                    ->where('id', $tokenId)->update(['revoked' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function isAccessTokenRevoked($tokenId)
    {
        return $this->database->table('oauth_access_tokens')
                    ->where('id', $tokenId)->where('revoked', 1)->exists();
    }
}
