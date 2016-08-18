<?php

use Laravel\Passport\Bridge\ClientRepository;

class BridgeClientRepositoryTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function test_can_get_client_for_auth_code_grant()
    {
        $clients = Mockery::mock('Laravel\Passport\ClientRepository');
        $client = new BridgeClientRepositoryTestClientStub;
        $clients->shouldReceive('findActive')->with(1)->andReturn($client);
        $repository = new ClientRepository($clients);

        $client = $repository->getClientEntity(1, 'authorization_code', 'secret', true);

        $this->assertInstanceOf('Laravel\Passport\Bridge\Client', $client);
        $this->assertNull($repository->getClientEntity(1, 'authorization_code', 'wrong-secret', true));
        $this->assertNull($repository->getClientEntity(1, 'client_credentials', 'wrong-secret', true));
    }

    public function test_can_get_client_for_client_credentials_grant()
    {
        $clients = Mockery::mock('Laravel\Passport\ClientRepository');
        $client = new BridgeClientRepositoryTestClientStub;
        $client->personal_access_client = true;
        $clients->shouldReceive('findActive')->with(1)->andReturn($client);
        $repository = new ClientRepository($clients);

        $this->assertInstanceOf('Laravel\Passport\Bridge\Client', $repository->getClientEntity(1, 'client_credentials', 'secret', true));
        $this->assertNull($repository->getClientEntity(1, 'authorization_code', 'secret', true));
    }
}

class BridgeClientRepositoryTestClientStub
{
    public $name = 'Client';
    public $redirect = 'http://localhost';
    public $secret = 'secret';
    public $personal_access_client = false;
    public $password_client = false;
    public function firstParty()
    {
        return $this->personal_access_client || $this->password_client;
    }
}
