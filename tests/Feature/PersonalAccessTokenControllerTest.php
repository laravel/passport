<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Support\Str;
use Laravel\Passport\Client;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\App\Models\User;
use Workbench\Database\Factories\UserFactory;

class PersonalAccessTokenControllerTest extends PassportTestCase
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

    public function test_tokens_can_be_retrieved_for_users()
    {
        $user = UserFactory::new()->create();

        $token = $this->createToken($user, ClientFactory::new()->asPersonalAccessTokenClient()->create());
        $this->createToken($user, ClientFactory::new()->create());
        $this->createToken($user, ClientFactory::new()->asPersonalAccessTokenClient()->create(['revoked' => true]));

        $this->actingAs($user)
            ->getJson('/oauth/personal-access-tokens')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $token->getKey());
    }

    public function test_tokens_can_be_stored()
    {
        Passport::tokensCan([
            'user' => 'first',
            'user-admin' => 'second',
        ]);

        $user = UserFactory::new()->create();
        $client = ClientFactory::new()->asPersonalAccessTokenClient()->create();

        $response = $this->actingAs($user)
            ->postJson('/oauth/personal-access-tokens', ['name' => 'token name', 'scopes' => ['user', 'user-admin']])
            ->assertOk()
            ->assertJsonStructure(['accessTokenId', 'accessToken', 'tokenType', 'expiresIn']);

        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $response->json('accessTokenId'),
            'user_id' => $user->getKey(),
            'client_id' => $client->getKey(),
            'name' => 'token name',
            'scopes' => '["user","user-admin"]',
        ]);
    }

    public function test_tokens_require_valid_attributes()
    {
        Passport::tokensCan([
            'user' => 'first',
        ]);

        $this->actingAs(UserFactory::new()->create())
            ->postJson('/oauth/personal-access-tokens', ['name' => '', 'scopes' => ['user', 'unknown']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'scopes']);

        $this->assertDatabaseCount('oauth_access_tokens', 0);
    }

    public function test_tokens_can_be_deleted()
    {
        $user = UserFactory::new()->create();
        $token = $this->createToken($user, ClientFactory::new()->asPersonalAccessTokenClient()->create());

        $this->actingAs($user)
            ->deleteJson('/oauth/personal-access-tokens/'.$token->getKey())
            ->assertNoContent();

        $this->assertTrue($token->refresh()->revoked);
    }

    public function test_not_found_response_is_returned_if_user_doesnt_have_token()
    {
        $token = $this->createToken(UserFactory::new()->create(), ClientFactory::new()->asPersonalAccessTokenClient()->create());

        $this->actingAs(UserFactory::new()->create())
            ->deleteJson('/oauth/personal-access-tokens/'.$token->getKey())
            ->assertNotFound();

        $this->assertFalse($token->refresh()->revoked);
    }

    private function createToken(User $user, Client $client): Token
    {
        return Passport::token()->forceCreate([
            'id' => Str::random(40),
            'user_id' => $user->getKey(),
            'client_id' => $client->getKey(),
            'scopes' => [],
            'revoked' => false,
            'expires_at' => now()->addDay(),
        ]);
    }
}
