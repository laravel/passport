<?php

use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;

class ClientRepositoryTest extends TestCase
{
    public function setUp()
    {
        $passwordOnlyClient = new \Laravel\Passport\Client([
            'id' => 5,
            'grant_types' => ['password'],
            'name' => 'Password only client',
            'password_client' => true,
            'secret' => 'secret',
        ]);

        $clientModelRepository = Mockery::mock(Laravel\Passport\ClientRepository::class);
        $clientModelRepository->shouldReceive('findActive')->with('passwordOnlyClient')->andReturn($passwordOnlyClient);

        $this->clientRepository = new Laravel\Passport\Bridge\ClientRepository($clientModelRepository);
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function test_password_only_client_is_permitted()
    {
        // client restricts the grant types - password grant must be accepted
        $client = $this->clientRepository->getClientEntity('passwordOnlyClient', 'password', 'secret');
        $this->assertEquals('passwordOnlyClient', $client->getIdentifier());
    }

    public function test_password_only_client_is_prevented()
    {
        // client restricts the grant types - client_credentials grant must not be accepted
        $client = $this->clientRepository->getClientEntity('passwordOnlyClient', 'client_credentials', 'secret');
        $this->assertEquals(null, $client);
    }
}
