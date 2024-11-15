<?php

namespace Laravel\Passport\Bridge;

use DateTimeImmutable;
use League\OAuth2\Server\Entities\DeviceCodeEntityInterface;
use League\OAuth2\Server\Entities\Traits\DeviceCodeTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class DeviceCode implements DeviceCodeEntityInterface
{
    use EntityTrait, DeviceCodeTrait, TokenEntityTrait;

    /**
     * Create a new device code instance.
     *
     * @param  non-empty-string|null  $identifier
     * @param  non-empty-string|null  $userIdentifier
     * @param  non-empty-string|null  $clientIdentifier
     * @param  string[]  $scopes
     */
    public function __construct(
        ?string $identifier = null,
        ?string $userIdentifier = null,
        ?string $clientIdentifier = null,
        array $scopes = [],
        bool $userApproved = false,
        ?DateTimeImmutable $lastPolledAt = null,
        ?DateTimeImmutable $expiryDateTime = null
    ) {
        if (! is_null($identifier)) {
            $this->setIdentifier($identifier);
        }

        if (! is_null($userIdentifier)) {
            $this->setUserIdentifier($userIdentifier);
        }

        if (! is_null($clientIdentifier)) {
            $this->setClient(new Client($clientIdentifier));
        }

        foreach ($scopes as $scope) {
            $this->addScope(new Scope($scope));
        }

        if ($userApproved) {
            $this->setUserApproved($userApproved);
        }

        if (! is_null($lastPolledAt)) {
            $this->setLastPolledAt($lastPolledAt);
        }

        if (! is_null($expiryDateTime)) {
            $this->setExpiryDateTime($expiryDateTime);
        }
    }
}
