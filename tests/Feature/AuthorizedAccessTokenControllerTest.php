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

class AuthorizedAccessTokenControllerTest extends PassportTestCase
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
        $thirdParty = ClientFactory::new()->for(UserFactory::new(), 'owner')->create();

        $token = $this->createToken($user, $thirdParty);
        $this->createToken($user, ClientFactory::new()->create());
        $this->createToken($user, ClientFactory::new()->for(UserFactory::new(), 'owner')->create(['revoked' => true]));
        $this->createToken(UserFactory::new()->create(), $thirdParty);

        $this->actingAs($user)
            ->getJson('/oauth/tokens')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $token->getKey());
    }

    public function test_tokens_can_be_deleted()
    {
        $user = UserFactory::new()->create();
        $token = $this->createToken($user, ClientFactory::new()->create());
        $refreshToken = Passport::refreshToken()->forceCreate([
            'id' => Str::random(40),
            'access_token_id' => $token->getKey(),
            'revoked' => false,
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->deleteJson('/oauth/tokens/'.$token->getKey())
            ->assertNoContent();

        $this->assertTrue($token->refresh()->revoked);
        $this->assertTrue($refreshToken->refresh()->revoked);
    }

    public function test_not_found_response_is_returned_if_user_doesnt_have_token()
    {
        $token = $this->createToken(UserFactory::new()->create(), ClientFactory::new()->create());

        $this->actingAs(UserFactory::new()->create())
            ->deleteJson('/oauth/tokens/'.$token->getKey())
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
