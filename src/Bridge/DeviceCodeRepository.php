<?php

namespace Laravel\Passport\Bridge;

use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\DeviceCodeEntityInterface;
use League\OAuth2\Server\Repositories\DeviceCodeRepositoryInterface;

class DeviceCodeRepository implements DeviceCodeRepositoryInterface
{
    use FormatsScopesForStorage;

    /**
     * {@inheritdoc}
     */
    public function getNewDeviceCode()
    {
        return new DeviceCode();
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewDeviceCode(DeviceCodeEntityInterface $deviceCodeEntity)
    {
        $attributes = [
            'id' => $deviceCodeEntity->getIdentifier(),
            'user_code' => $deviceCodeEntity->getUserCode(),
            'client_id' => $deviceCodeEntity->getClient()->getIdentifier(),
            'scopes' => $this->formatScopesForStorage($deviceCodeEntity->getScopes()),
            'revoked' => false,
            'expires_at' => $deviceCodeEntity->getExpiryDateTime(),
        ];

        Passport::deviceCode()->setRawAttributes($attributes)->save();
    }

    /**
     * {@inheritdoc}
     */
    public function getDeviceCodeEntityByDeviceCode($codeId, $grantType, ClientEntityInterface $clientEntity)
    {
        $deviceCode = Passport::deviceCode()->where('id', $codeId)->first();

        if (!$deviceCode) {
            return;
        }

        $deviceCodeEntity = new DeviceCode();
        $deviceCodeEntity->setIdentifier($deviceCode->id);
        $deviceCodeEntity->setUserCode($deviceCode->user_code);

        foreach ($deviceCode->scopes as $scope) {
            $deviceCodeEntity->addScope($scope);
        }

        $deviceCodeEntity->setClient($clientEntity);

        $deviceCodeEntity->setUserIdentifier($deviceCode->user_id);

        return $deviceCodeEntity;
    }

    /**
     * {@inheritdoc}
     */
    public function revokeDeviceCode($codeId)
    {
        Passport::deviceCode()->where('id', $codeId)->update(['revoked' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function isDeviceCodeRevoked($codeId)
    {
        return Passport::deviceCode()->where('id', $codeId)->where('revoked', 1)->exists();
    }
}
