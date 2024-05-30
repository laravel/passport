<?php

namespace Laravel\Passport\Bridge;

use DateTimeImmutable;
use League\OAuth2\Server\Entities\DeviceCodeEntityInterface;
use League\OAuth2\Server\Entities\Traits\DeviceCodeTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class DeviceCode implements DeviceCodeEntityInterface
{
    use EntityTrait;
    use DeviceCodeTrait {
        setLastPolledAt as traitSetLastPolledAt;
        setUserApproved as traitSetUserApproved;
    }
    use TokenEntityTrait;

    /**
     * Determine if the "user identifier" and "user approved" properties has changed.
     */
    private bool $isUserDirty = false;

    /**
     * Determine if the "last polled at" property has changed.
     */
    private bool $isLastPolledAtDirty = false;

    /**
     * Create a new device code instance.
     *
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
            $this->traitSetUserApproved($userApproved);
        }

        if (! is_null($lastPolledAt)) {
            $this->traitSetLastPolledAt($lastPolledAt);
        }

        if (! is_null($expiryDateTime)) {
            $this->setExpiryDateTime($expiryDateTime);
        }

        $this->isUserDirty = false;
        $this->isLastPolledAtDirty = false;
    }

    /**
     * {@inheritdoc}
     */
    public function setUserApproved(bool $userApproved): void
    {
        $this->isUserDirty = true;

        $this->traitSetUserApproved($userApproved);
    }

    /**
     * {@inheritdoc}
     */
    public function setLastPolledAt(DateTimeImmutable $lastPolledAt): void
    {
        $this->isLastPolledAtDirty = true;

        $this->traitSetLastPolledAt($lastPolledAt);
    }

    /**
     * Determine if the "user identifier" and "user approved" properties has changed.
     */
    public function isUserDirty(): bool
    {
        return $this->isUserDirty;
    }

    /**
     * Determine if the "last polled at" property has changed.
     */
    public function isLastPolledAtDirty(): bool
    {
        return $this->isLastPolledAtDirty;
    }
}
