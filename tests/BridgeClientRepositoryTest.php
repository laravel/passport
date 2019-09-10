<?php

namespace Laravel\Passport\Tests;

use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\ClientRepository as BridgeClientRepository;
use Laravel\Passport\ClientRepository;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class BridgeClientRepositoryTest extends TestCase
{
    /**
     * @var \Laravel\Passport\ClientRepository
     */
    private $clientModelRepository;

    /**
     * @var \Laravel\Passport\Bridge\ClientRepository
     */
    private $repository;

    protected function setUp(): void
    {
        $clientModelRepository = m::mock(ClientRepository::class);
        $clientModelRepository->shouldReceive('findActive')
            ->with(1)
            ->andReturn(new BridgeClientRepositoryTestClientStub);

        $this->clientModelRepository = $clientModelRepository;
        $this->repository = new BridgeClientRepository($clientModelRepository);
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
        $this->assertEquals('1', $client->getIdentifier());
        $this->assertEquals('Client', $client->getName());
        $this->assertEquals(['http://localhost'], $client->getRedirectUri());
        $this->assertTrue($client->isConfidential());
    }

    public function test_can_validate_client_for_auth_code_grant()
    {
        $this->assertTrue($this->repository->validateClient(1, 'secret', 'authorization_code'));
        $this->assertFalse($this->repository->validateClient(1, 'wrong-secret', 'authorization_code'));
        $this->assertFalse($this->repository->validateClient(1, 'wrong-secret', 'client_credentials'));
    }

    public function test_can_validate_client_for_client_credentials_grant()
    {
        $client = $this->clientModelRepository->findActive(1);
        $client->personal_access_client = true;

        $this->assertTrue($this->repository->validateClient(1, 'secret', 'client_credentials'));
        $this->assertFalse($this->repository->validateClient(1, 'wrong-secret', 'client_credentials'));
        $this->assertFalse($this->repository->validateClient(1, 'secret', 'authorization_code'));
    }

    public function test_password_grant_is_permitted()
    {
        $client = $this->clientModelRepository->findActive(1);
        $client->password_client = true;

        $this->assertTrue($this->repository->validateClient(1, 'secret', 'password'));
    }

    public function test_public_client_password_grant_is_permitted()
    {
        $client = $this->clientModelRepository->findActive(1);
        $client->password_client = true;
        $client->secret = null;

        $this->assertTrue($this->repository->validateClient(1, null, 'password'));
    }

    public function test_password_grant_is_prevented()
    {
        $this->assertFalse($this->repository->validateClient(1, 'secret', 'password'));
    }

    public function test_authorization_code_grant_is_permitted()
    {
        $this->assertTrue($this->repository->validateClient(1, 'secret', 'authorization_code'));
    }

    public function test_public_client_authorization_code_grant_is_permitted()
    {
        $client = $this->clientModelRepository->findActive(1);
        $client->secret = null;

        $this->assertTrue($this->repository->validateClient(1, null, 'authorization_code'));
    }

    public function test_authorization_code_grant_is_prevented()
    {
        $client = $this->clientModelRepository->findActive(1);
        $client->password_client = true;

        $this->assertFalse($this->repository->validateClient(1, 'secret', 'authorization_code'));
    }

    public function test_personal_access_grant_is_permitted()
    {
        $client = $this->clientModelRepository->findActive(1);
        $client->personal_access_client = true;

        $this->assertTrue($this->repository->validateClient(1, 'secret', 'personal_access'));
    }

    public function test_personal_access_grant_is_prevented()
    {
        $this->assertFalse($this->repository->validateClient(1, 'secret', 'personal_access'));
    }

    public function test_public_client_personal_access_grant_is_prevented()
    {
        $client = $this->clientModelRepository->findActive(1);
        $client->personal_access_client = true;
        $client->secret = null;

        $this->assertFalse($this->repository->validateClient(1, null, 'personal_access'));
    }

    public function test_client_credentials_grant_is_permitted()
    {
        $this->assertTrue($this->repository->validateClient(1, 'secret', 'client_credentials'));
    }

    public function test_client_credentials_grant_is_prevented()
    {
        $client = $this->clientModelRepository->findActive(1);
        $client->secret = null;

        $this->assertFalse($this->repository->validateClient(1, 'secret', 'client_credentials'));
    }

    public function test_grant_types_allows_request()
    {
        $client = $this->clientModelRepository->findActive(1);
        $client->grant_types = ['client_credentials'];

        $this->assertTrue($this->repository->validateClient(1, 'secret', 'client_credentials'));
    }

    public function test_grant_types_disallows_request()
    {
        $client = $this->clientModelRepository->findActive(1);
        $client->grant_types = ['client_credentials'];

        $this->assertFalse($this->repository->validateClient(1, 'secret', 'authorization_code'));
    }

    public function test_refresh_grant_is_permitted()
    {
        $this->assertTrue($this->repository->validateClient(1, 'secret', 'refresh_token'));
    }

    public function test_public_refresh_grant_is_permitted()
    {
        $client = $this->clientModelRepository->findActive(1);
        $client->secret = null;

        $this->assertTrue($this->repository->validateClient(1, null, 'refresh_token'));
    }

    public function test_refresh_grant_is_prevented()
    {
        $this->assertFalse($this->repository->validateClient(1, 'wrong-secret', 'refresh_token'));
    }
}

class BridgeClientRepositoryTestClientStub
{
    public $name = 'Client';

    public $redirect = 'http://localhost';

    public $secret = 'secret';

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
