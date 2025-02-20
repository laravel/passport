<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Contracts\Hashing\Hasher;
use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\ClientRepository as BridgeClientRepository;
use Laravel\Passport\ClientRepository;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class BridgeClientRepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Laravel\Passport\ClientRepository
     */
    protected $clientModelRepository;

    /**
     * @var \Laravel\Passport\Bridge\ClientRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        $clientModelRepository = m::mock(ClientRepository::class);
        $clientModelRepository->shouldReceive('findActive')
            ->with(1)
            ->andReturn($client = new BridgeClientRepositoryTestClientStub);

        $hasher = m::mock(Hasher::class);
        $hasher->shouldReceive('check')->with('secret', $client->secret)->andReturn(true);
        $hasher->shouldReceive('check')->withAnyArgs()->andReturn(false);

        $this->clientModelRepository = $clientModelRepository;
        $this->repository = new BridgeClientRepository($clientModelRepository, $hasher);
    }

    protected function tearDown(): void
    {
        m::close();

        unset($this->clientModelRepository, $this->repository);
    }

    public function test_can_get_client()
    {
        $client = $this->repository->getClientEntity(1);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertSame('1', $client->getIdentifier());
        $this->assertSame('Client', $client->getName());
        $this->assertEquals(['http://localhost'], $client->getRedirectUri());
        $this->assertTrue($client->isConfidential());
    }

    public function test_can_validate_client()
    {
        $this->assertTrue($this->repository->validateClient(1, 'secret', 'authorization_code'));
        $this->assertFalse($this->repository->validateClient(1, 'wrong-secret', 'authorization_code'));
        $this->assertFalse($this->repository->validateClient(1, 'wrong-secret', 'client_credentials'));
        $this->assertFalse($this->repository->validateClient(1, null, 'authorization_code'));
        $this->assertFalse($this->repository->validateClient(1, '', 'authorization_code'));
        $this->assertTrue($this->repository->validateClient(1, 'secret', null));
        $this->assertFalse($this->repository->validateClient(1, 'wrong-secret', null));
        $this->assertFalse($this->repository->validateClient(1, null, null));
        $this->assertFalse($this->repository->validateClient(1, '', null));
    }
}

class BridgeClientRepositoryTestClientStub extends \Laravel\Passport\Client
{
    protected $attributes = [
        'id' => 1,
        'name' => 'Client',
        'redirect_uris' => '["http://localhost"]',
        'secret' => '$2y$10$WgqU4wQpfsARCIQk.nPSOOiNkrMpPVxQiLCFUt8comvQwh1z6WFMG',
        'grant_types' => '["authorization_code","refresh_token"]',
    ];
}
