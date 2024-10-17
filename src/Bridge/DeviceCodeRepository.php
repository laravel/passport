<?php

namespace Laravel\Passport\Bridge;

use DateTime;
use Illuminate\Support\Facades\Date;
use Laravel\Passport\DeviceCode as DeviceCodeModel;
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
        Passport::deviceCode()->newQuery()->upsert([
            'id' => $deviceCodeEntity->getIdentifier(),
            'user_id' => $deviceCodeEntity->getUserIdentifier(),
            'client_id' => $deviceCodeEntity->getClient()->getIdentifier(),
            'user_code' => $deviceCodeEntity->getUserCode(),
            'scopes' => $this->formatScopesForStorage($deviceCodeEntity->getScopes()),
            'revoked' => false,
            'user_approved_at' => $deviceCodeEntity->getUserApproved() ? new DateTime : null,
            'last_polled_at' => $deviceCodeEntity->getLastPolledAt(),
            'expires_at' => $deviceCodeEntity->getExpiryDateTime(),
        ], 'id', ['user_id', 'user_approved_at', 'last_polled_at']);
    }

    /**
     * {@inheritdoc}
     */
    public function getDeviceCodeEntityByDeviceCode(string $deviceCode): ?DeviceCodeEntityInterface
    {
        $record = Passport::deviceCode()->newQuery()->whereKey($deviceCode)->where(['revoked' => false])->first();

        return $record ? $this->fromDeviceCodeModel($record) : null;
    }

    /*
     * Get the device code entity by the given user code.
     */
    public function getDeviceCodeEntityByUserCode(string $userCode): ?DeviceCodeEntityInterface
    {
        $record = Passport::deviceCode()->newQuery()
            ->where('user_code', $userCode)
            ->where('expires_at', '>', Date::now())
            ->where('revoked', false)
            ->first();

        return $record ? $this->fromDeviceCodeModel($record) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function revokeDeviceCode(string $codeId): void
    {
        Passport::deviceCode()->newQuery()->whereKey($codeId)->update(['revoked' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function isDeviceCodeRevoked(string $codeId): bool
    {
        return Passport::deviceCode()->newQuery()->whereKey($codeId)->where('revoked', false)->doesntExist();
    }

    /**
     * Create a new device code entity from the given device code model instance.
     */
    protected function fromDeviceCodeModel(DeviceCodeModel $model): DeviceCodeEntityInterface
    {
        return new DeviceCode(
            $model->getKey(),
            $model->user_id,
            $model->client_id,
            $model->user_code,
            $model->scopes,
            ! is_null($model->user_approved_at),
            $model->last_polled_at?->toDateTimeImmutable(),
            $model->expires_at?->toDateTimeImmutable()
        );
    }
}
