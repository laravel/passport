<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Database\Factories\ClientFactory;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class ClientRepositoryTest extends PassportTestCase
{
    use WithLaravelMigrations;

    public function test_clients_can_be_retrieved_for_owner()
    {
        $user = UserFactory::new()->create();
        $client = ClientFactory::new()->for($user, 'owner')->create();
        ClientFactory::new()->for($user, 'owner')->create(['revoked' => true]);
        $other = ClientFactory::new()->for(UserFactory::new(), 'owner')->create();

        $repository = app(ClientRepository::class);

        $this->assertEquals([$client->getKey()], $repository->forUser($user)->modelKeys());
        $this->assertTrue($client->is($repository->findForUser($client->getKey(), $user)));
        $this->assertNull($repository->findForUser($other->getKey(), $user));
    }

    public function test_clients_can_be_retrieved_for_legacy_user_id()
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->index();
        });

        $user = UserFactory::new()->create();
        $client = ClientFactory::new()->create(['user_id' => $user->getKey()]);
        ClientFactory::new()->create(['user_id' => $user->getKey(), 'revoked' => true]);
        $other = ClientFactory::new()->create(['user_id' => UserFactory::new()->create()->getKey()]);

        $repository = app(ClientRepository::class);

        $this->assertEquals([$client->getKey()], $repository->forUser($user)->modelKeys());
        $this->assertTrue($client->is($repository->findForUser($client->getKey(), $user)));
        $this->assertNull($repository->findForUser($other->getKey(), $user));
    }

    public function test_created_clients_can_be_retrieved_for_user()
    {
        $user = UserFactory::new()->create();

        $repository = app(ClientRepository::class);
        $client = $repository->createAuthorizationCodeGrantClient('name', ['https://localhost'], user: $user);

        $this->assertEquals([$client->getKey()], $repository->forUser($user)->modelKeys());
        $this->assertTrue($client->is($repository->findForUser($client->getKey(), $user)));
    }
}
