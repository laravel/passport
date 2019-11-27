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
        $record = Passport::deviceCode()->where('id', $codeId)->first();

        if (!$record) {
            return;
        }

        $deviceCode = new DeviceCode();
        $deviceCode->setIdentifier($record->id);
        $deviceCode->setUserCode($record->user_code);

        foreach ($record->scopes as $scope) {
            $deviceCode->addScope($scope);
        }

        $deviceCode->setClient($clientEntity);

        $deviceCode->setUserIdentifier($record->user_id);

        return $deviceCode;
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
