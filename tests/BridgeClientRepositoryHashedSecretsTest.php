<?php

namespace Laravel\Passport\Tests;

use Illuminate\Contracts\Hashing\Hasher;
use Laravel\Passport\Bridge\ClientRepository as BridgeClientRepository;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Mockery as m;

class BridgeClientRepositoryHashedSecretsTest extends BridgeClientRepositoryTest
{
    protected function setUp(): void
    {
        Passport::useHashedClientSecrets();

        $hasher = m::mock(Hasher::class);

        $hasher->shouldReceive('check')
            ->with('secret', 'hashedsecret')
            ->andReturnTrue();

        $hasher->shouldReceive('check')
            ->with('wrong-secret', 'hashedsecret')
            ->andReturnFalse();

        $clientModelRepository = m::mock(ClientRepository::class);
        $clientModelRepository->shouldReceive('findActive')
            ->with(1)
            ->andReturn(new BridgeClientRepositoryHashedTestClientStub);

        $this->clientModelRepository = $clientModelRepository;
        $this->hasher = $hasher;

        $this->repository = new BridgeClientRepository($clientModelRepository, $hasher);
    }
}

class BridgeClientRepositoryHashedTestClientStub
{
    public $name = 'Client';

    public $redirect = 'http://localhost';

    public $secret = 'hashedsecret';

    public $personal_access_client = false;

    public $password_client = false;

    public $grant_types;

    public function firstParty()
    {
        return $this->personal_access_client || $this->password_client;
    }

    public function confidential()
    {
        return ! empty($this->secret);
    }
}
