<?php

use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;

class ClientRepositoryTest extends TestCase
{
    public function setUp()
    {
        $passwordClient = new \Laravel\Passport\Client([
            'id' => 1,
            'name' => 'Password client',
            'password_client' => true,
            'secret' => 'secret',
        ]);

        $personalAccessClient = new \Laravel\Passport\Client([
            'id' => 2,
            'name' => 'Personal access client',
            'personal_access_client' => true,
            'secret' => 'secret',
        ]);

        $confidentialClient = new \Laravel\Passport\Client([
            'id' => 3,
            'name' => 'Confidential client credentials client',
            'secret' => 'secret',
        ]);

        $publicClient = new \Laravel\Passport\Client([
            'id' => 4,
            'name' => 'Public client',
        ]);

        $clientModelRepository = Mockery::mock(Laravel\Passport\ClientRepository::class);
        $clientModelRepository->shouldReceive('findActive')->with('passwordClient')->andReturn($passwordClient);
        $clientModelRepository->shouldReceive('findActive')->with('personalAccessClient')->andReturn($personalAccessClient);
        $clientModelRepository->shouldReceive('findActive')->with('confidentialClient')->andReturn($confidentialClient);
        $clientModelRepository->shouldReceive('findActive')->with('publicClient')->andReturn($publicClient);

        $this->clientRepository = new Laravel\Passport\Bridge\ClientRepository($clientModelRepository);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function test_password_grant_is_permitted()
    {
        // password requires the password_client flag - passwordClient must be accepted
        $client = $this->clientRepository->getClientEntity('passwordClient', 'password', 'secret');
        $this->assertEquals('passwordClient', $client->getIdentifier());
    }

    public function test_password_grant_is_prevented()
    {
        // password requires the password_client flag - personalAccessClient must not be accepted
        $client = $this->clientRepository->getClientEntity('personalAccessClient', 'password', 'secret');
        $this->assertEquals(null, $client);
    }

    public function test_authorization_code_grant_is_permitted()
    {
        // authorization_code requires a third party client - confidentialClient must be accepted
        $client = $this->clientRepository->getClientEntity('confidentialClient', 'authorization_code', 'secret');
        $this->assertEquals('confidentialClient', $client->getIdentifier());
    }

    public function test_authorization_code_grant_is_prevented()
    {
        // authorization_code requires a third party client - passwordClient must not be accepted
        $client = $this->clientRepository->getClientEntity('passwordClient', 'authorization_code', 'secret');
        $this->assertEquals(null, $client);
    }

    public function test_personal_access_grant_is_permitted()
    {
        // personal_access grant requires the personal_access_client flag - personalAccessClient must be accepted
        $client = $this->clientRepository->getClientEntity('personalAccessClient', 'personal_access', 'secret');
        $this->assertEquals('personalAccessClient', $client->getIdentifier());
    }

    public function test_personal_access_grant_is_prevented()
    {
        // personal_access grant requires the personal_access_client flag - passwordClient must not be accepted
        $client = $this->clientRepository->getClientEntity('passwordClient', 'personal_access', 'secret');
        $this->assertEquals(null, $client);
    }

    public function test_client_credentials_grant_is_permitted()
    {
        // client_credentials grant requires a secret - confidentialClient must be accepted
        $client = $this->clientRepository->getClientEntity('confidentialClient', 'client_credentials', 'secret');
        $this->assertEquals('confidentialClient', $client->getIdentifier());
    }

    public function test_client_credentials_grant_is_prevented()
    {
        // client_credentials grant requires a secret - publicClient must not be accepted
        $client = $this->clientRepository->getClientEntity('publicClient', 'client_credentials', 'secret');
        $this->assertEquals(null, $client);
    }
}
