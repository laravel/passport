<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class ClientControllerTest extends PassportTestCase
{
    use WithLaravelMigrations;

    protected function tearDown(): void
    {
        Passport::$registersJsonApiRoutes = false;

        parent::tearDown();
    }

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        Passport::$registersJsonApiRoutes = true;
    }

    public function test_all_the_clients_for_the_current_user_can_be_retrieved()
    {
        $user = UserFactory::new()->create();
        $client = ClientFactory::new()->for($user, 'owner')->create(['name' => 'Mine']);
        ClientFactory::new()->for($user, 'owner')->create(['revoked' => true]);
        ClientFactory::new()->for(UserFactory::new(), 'owner')->create();

        $this->actingAs($user)
            ->getJson('/oauth/clients')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $client->getKey())
            ->assertJsonPath('0.name', 'Mine');
    }

    public function test_clients_can_be_stored()
    {
        $user = UserFactory::new()->create();

        $response = $this->actingAs($user)
            ->postJson('/oauth/clients', ['name' => 'client name', 'redirect' => 'https://localhost'])
            ->assertCreated()
            ->assertJsonPath('name', 'client name')
            ->assertJsonPath('redirect_uris', ['https://localhost'])
            ->assertJsonMissingPath('secret');

        $client = Passport::client()->findOrFail($response->json('id'));

        $this->assertTrue($user->is($client->owner));
        $this->assertTrue($client->confidential());
        $this->assertTrue(Hash::check($response->json('plain_secret'), $client->secret));

        $this->actingAs($user)
            ->getJson('/oauth/clients')
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $client->getKey());
    }

    public function test_public_clients_can_be_stored()
    {
        $response = $this->actingAs(UserFactory::new()->create())
            ->postJson('/oauth/clients', ['name' => 'client name', 'redirect' => 'https://localhost', 'confidential' => false])
            ->assertCreated()
            ->assertJsonMissingPath('plain_secret');

        $this->assertFalse(Passport::client()->findOrFail($response->json('id'))->confidential());
    }

    public function test_clients_require_valid_attributes()
    {
        $this->actingAs(UserFactory::new()->create())
            ->postJson('/oauth/clients', ['name' => '', 'redirect' => 'https://localhost,invalid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'redirect']);

        $this->assertDatabaseCount('oauth_clients', 0);
    }

    public function test_clients_can_be_updated()
    {
        $user = UserFactory::new()->create();
        $client = ClientFactory::new()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->putJson('/oauth/clients/'.$client->getKey(), ['name' => 'new name', 'redirect' => 'https://one.test,https://two.test'])
            ->assertOk()
            ->assertJsonPath('name', 'new name');

        $client->refresh();

        $this->assertSame('new name', $client->name);
        $this->assertSame(['https://one.test', 'https://two.test'], $client->redirect_uris);
    }

    public function test_404_response_if_client_doesnt_belong_to_user()
    {
        $client = ClientFactory::new()->for(UserFactory::new(), 'owner')->create(['name' => 'original']);

        $this->actingAs(UserFactory::new()->create())
            ->putJson('/oauth/clients/'.$client->getKey(), ['name' => 'new name', 'redirect' => 'https://localhost'])
            ->assertNotFound();

        $this->assertSame('original', $client->refresh()->name);
    }

    public function test_clients_can_be_deleted()
    {
        $user = UserFactory::new()->create();
        $client = ClientFactory::new()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->deleteJson('/oauth/clients/'.$client->getKey())
            ->assertNoContent();

        $this->assertTrue($client->refresh()->revoked);
    }

    public function test_404_response_if_client_doesnt_belong_to_user_on_delete()
    {
        $client = ClientFactory::new()->for(UserFactory::new(), 'owner')->create();

        $this->actingAs(UserFactory::new()->create())
            ->deleteJson('/oauth/clients/'.$client->getKey())
            ->assertNotFound();

        $this->assertFalse($client->refresh()->revoked);
    }

    public function test_all_the_clients_for_the_current_user_can_be_retrieved_via_legacy_user_id()
    {
        $this->addLegacyUserIdColumn();

        $user = UserFactory::new()->create();
        $client = ClientFactory::new()->create(['user_id' => $user->getKey()]);
        ClientFactory::new()->create(['user_id' => $user->getKey(), 'revoked' => true]);
        ClientFactory::new()->create(['user_id' => UserFactory::new()->create()->getKey()]);

        $this->actingAs($user)
            ->getJson('/oauth/clients')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $client->getKey());
    }

    public function test_clients_can_be_stored_via_legacy_user_id()
    {
        $this->addLegacyUserIdColumn();

        $user = UserFactory::new()->create();

        $response = $this->actingAs($user)
            ->postJson('/oauth/clients', ['name' => 'client name', 'redirect' => 'https://localhost'])
            ->assertCreated();

        $this->assertEquals($user->getKey(), Passport::client()->findOrFail($response->json('id'))->user_id);

        $this->actingAs($user)
            ->getJson('/oauth/clients')
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $response->json('id'));
    }

    public function test_clients_can_be_updated_via_legacy_user_id()
    {
        $this->addLegacyUserIdColumn();

        $user = UserFactory::new()->create();
        $client = ClientFactory::new()->create(['user_id' => $user->getKey()]);
        $other = ClientFactory::new()->create(['user_id' => UserFactory::new()->create()->getKey()]);

        $this->actingAs($user)
            ->putJson('/oauth/clients/'.$client->getKey(), ['name' => 'new name', 'redirect' => 'https://localhost'])
            ->assertOk()
            ->assertJsonPath('name', 'new name');

        $this->actingAs($user)
            ->putJson('/oauth/clients/'.$other->getKey(), ['name' => 'new name', 'redirect' => 'https://localhost'])
            ->assertNotFound();
    }

    protected function addLegacyUserIdColumn(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->index();
        });
    }
}
