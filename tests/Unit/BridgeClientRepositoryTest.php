<?php

namespace Laravel\Passport\Tests\Unit;

use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Double;
use Illuminate\Contracts\Hashing\Hasher;
use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\ClientRepository as BridgeClientRepository;
use Laravel\Passport\ClientRepository;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class BridgeClientRepositoryTest extends TestCase
{
    use VerifiesDoubles;

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
        $clientModelRepository = Double::for(ClientRepository::class);
        $clientModelRepository->allows('findActive')->with(1)->returns($client = new BridgeClientRepositoryTestClientStub);

        $hasher = Double::for(Hasher::class);
        $hasher->allows('check')->with('secret', $client->secret)->returns(true);
        $hasher->allows('check')->returns(false);

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
