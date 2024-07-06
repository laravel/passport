<?php

namespace Laravel\Passport\Bridge;

use DateTime;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Entities\DeviceCodeEntityInterface;
use League\OAuth2\Server\Repositories\DeviceCodeRepositoryInterface;

class DeviceCodeRepository implements DeviceCodeRepositoryInterface
{
    use FormatsScopesForStorage;

    /**
     * {@inheritdoc}
     */
    public function getNewDeviceCode(): DeviceCodeEntityInterface
    {
        return new DeviceCode;
    }

    /**
     * {@inheritdoc}
     */
    public function persistDeviceCode(DeviceCodeEntityInterface $deviceCodeEntity): void
    {
        if ($deviceCodeEntity->isLastPolledAtDirty()) {
            Passport::deviceCode()->whereKey($deviceCodeEntity->getIdentifier())->update([
                'last_polled_at' => $deviceCodeEntity->getLastPolledAt(),
            ]);
        } elseif ($deviceCodeEntity->isUserDirty()) {
            Passport::deviceCode()->whereKey($deviceCodeEntity->getIdentifier())->update([
                'user_id' => $deviceCodeEntity->getUserIdentifier(),
                'user_approved_at' => $deviceCodeEntity->getUserApproved() ? new DateTime : null,
            ]);
        } else {
            Passport::deviceCode()->forceFill([
                'id' => $deviceCodeEntity->getIdentifier(),
                'user_id' => null,
                'client_id' => $deviceCodeEntity->getClient()->getIdentifier(),
                'user_code' => $deviceCodeEntity->getUserCode(),
                'scopes' => $this->scopesToArray($deviceCodeEntity->getScopes()),
                'revoked' => false,
                'user_approved_at' => null,
                'last_polled_at' => null,
                'expires_at' => $deviceCodeEntity->getExpiryDateTime(),
            ])->save();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDeviceCodeEntityByDeviceCode(string $deviceCode): ?DeviceCodeEntityInterface
    {
        $record = Passport::deviceCode()->whereKey($deviceCode)->where(['revoked' => false])->first();

        return $record ? new DeviceCode(
            $record->getKey(),
            $record->user_id,
            $record->client_id,
            $record->scopes,
            ! is_null($record->user_approved_at),
            $record->last_polled_at,
            $record->expires_at
        ) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function revokeDeviceCode(string $codeId): void
    {
        Passport::deviceCode()->whereKey($codeId)->update(['revoked' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function isDeviceCodeRevoked(string $codeId): bool
    {
        return Passport::deviceCode()->whereKey($codeId)->where('revoked', false)->doesntExist();
    }
}
