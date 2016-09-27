<?php

namespace Laravel\Passport\Bridge;

use Laravel\Passport\AuthCodeRepository as AuthCodeModelRepository;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    use FormatsScopesForStorage;

    /**
     * The auth code model repository.
     *
     * @var \Laravel\Passport\AuthCodeRepository
     */
    protected $authCodes;

    /**
     * Create a new repository instance.
     *
     * @param  \Laravel\Passport\AuthCodeRepository  $authCodes
     * @return void
     */
    public function __construct(AuthCodeModelRepository $authCodes)
    {
        $this->authCodes = $authCodes;
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
        $this->authCodes->create([
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
        $this->authCodes->revoke($codeId);
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthCodeRevoked($codeId)
    {
        return $this->authCodes->revoked($codeId);
    }
}
