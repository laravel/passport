<?php

namespace Laravel\Passport\Bridge;

use Illuminate\Database\Connection;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

class AuthCodeRepository implements AuthCodeRepositoryInterface
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
    public function getNewAuthCode()
    {
        return new AuthCode;
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity)
    {
        $this->database->table('oauth_auth_codes')->insert([
            'id' => $authCodeEntity->getIdentifier(),
            'user_id' => $authCodeEntity->getUserIdentifier(),
            'client_id' => $authCodeEntity->getClient()->getIdentifier(),
            'scopes' => $this->formatScopesForStorage($authCodeEntity->getScopes()),
            'revoked' => false,
            'expires_at' => $authCodeEntity->getExpiryDateTime(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAuthCode($codeId)
    {
        $this->database->table('oauth_auth_codes')
                    ->where('id', $codeId)->update(['revoked' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthCodeRevoked($codeId)
    {
        return $this->database->table('oauth_auth_codes')
                    ->where('id', $codeId)->where('revoked', 1)->exists();
    }
}
