<?php

namespace Laravel\Passport\Tests\Feature;

use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\ClientRepository;
use Laravel\Passport\Database\Factories\ClientFactory;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;

class BridgeClientRepositoryTest extends PassportTestCase
{
    use WithLaravelMigrations;

    public function test_can_get_client()
    {
        $model = ClientFactory::new()->create([
            'name' => 'Client',
            'redirect_uris' => ['http://localhost'],
        ]);

        $client = app(ClientRepository::class)->getClientEntity($model->getKey());

        $this->assertInstanceOf(Client::class, $client);
        $this->assertSame($model->getKey(), $client->getIdentifier());
        $this->assertSame('Client', $client->getName());
        $this->assertEquals(['http://localhost'], $client->getRedirectUri());
        $this->assertTrue($client->isConfidential());
    }

    public function test_can_get_public_client()
    {
        $model = ClientFactory::new()->asPublic()->create();

        $this->assertFalse(app(ClientRepository::class)->getClientEntity($model->getKey())->isConfidential());
    }

    public function test_cannot_get_revoked_or_missing_client()
    {
        $revoked = ClientFactory::new()->create(['revoked' => true]);

        $repository = app(ClientRepository::class);

        $this->assertNull($repository->getClientEntity($revoked->getKey()));
        $this->assertNull($repository->getClientEntity('missing'));
    }

    public function test_can_validate_client()
    {
        $client = ClientFactory::new()->create();
        $secret = $client->plainSecret;

        $repository = app(ClientRepository::class);

        foreach (['authorization_code', 'client_credentials', null] as $grantType) {
            $this->assertTrue($repository->validateClient($client->getKey(), $secret, $grantType));
            $this->assertFalse($repository->validateClient($client->getKey(), 'wrong-secret', $grantType));
            $this->assertFalse($repository->validateClient($client->getKey(), null, $grantType));
            $this->assertFalse($repository->validateClient($client->getKey(), '', $grantType));
        }
    }

    public function test_cannot_validate_revoked_or_public_client()
    {
        $revoked = ClientFactory::new()->create(['revoked' => true]);
        $public = ClientFactory::new()->asPublic()->create();

        $repository = app(ClientRepository::class);

        $this->assertFalse($repository->validateClient($revoked->getKey(), $revoked->plainSecret, 'authorization_code'));
        $this->assertFalse($repository->validateClient($public->getKey(), 'secret', 'authorization_code'));
    }
}
