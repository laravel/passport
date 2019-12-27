<?php

namespace Laravel\Passport\Tests;

use Laravel\Passport\Bridge\ClientRepository as BridgeClientRepository;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Mockery as m;

class BridgeClientRepositoryHashedSecretsTest extends BridgeClientRepositoryTest
{
    protected function setUp(): void
    {
        Passport::hashClientSecrets();

        $clientModelRepository = m::mock(ClientRepository::class);
        $clientModelRepository->shouldReceive('findActive')
            ->with(1)
            ->andReturn(new BridgeClientRepositoryHashedTestClientStub);

        $this->clientModelRepository = $clientModelRepository;
        $this->repository = new BridgeClientRepository($clientModelRepository);
    }
}

class BridgeClientRepositoryHashedTestClientStub extends BridgeClientRepositoryTestClientStub
{
    public $secret = '$2y$10$WgqU4wQpfsARCIQk.nPSOOiNkrMpPVxQiLCFUt8comvQwh1z6WFMG';
}
