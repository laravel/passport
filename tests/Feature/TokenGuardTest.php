<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Once;
use Laravel\Passport\Database\Factories\ClientFactory;
use League\OAuth2\Server\Exception\OAuthServerException;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class TokenGuardTest extends PassportTestCase
{
    use WithLaravelMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/guard', fn () => [
            'user' => auth('api')->user()?->getKey(),
            'token' => auth('api')->user()?->currentAccessToken()?->oauth_access_token_id,
            'client' => auth('api')->client()?->getKey(),
        ]);

        Route::get('/guard-repeated', function () {
            $client = auth('api')->client();

            DB::enableQueryLog();
            $sameClient = $client === auth('api')->client();
            $clientQueries = count(DB::getQueryLog());

            $user = auth('api')->user();

            DB::flushQueryLog();
            $sameUser = $user === auth('api')->user();
            $userQueries = count(DB::getQueryLog());

            return [
                'same_client' => $sameClient,
                'client_queries' => $clientQueries,
                'same_user' => $sameUser,
                'user_queries' => $userQueries,
            ];
        });
    }

    public function test_user_can_be_pulled_via_bearer_token()
    {
        $user = UserFactory::new()->create();
        $client = ClientFactory::new()->asPersonalAccessTokenClient()->create();
        $result = $user->createToken('test');

        $this->withToken($result->accessToken)
            ->getJson('/guard')
            ->assertExactJson([
                'user' => $user->getKey(),
                'token' => $result->accessTokenId,
                'client' => $client->getKey(),
            ]);
    }

    public function test_user_and_client_are_resolved_only_once()
    {
        $user = UserFactory::new()->create();
        ClientFactory::new()->asPersonalAccessTokenClient()->create();

        $this->withToken($user->createToken('test')->accessToken)
            ->getJson('/guard-repeated')
            ->assertExactJson([
                'same_client' => true,
                'client_queries' => 0,
                'same_user' => true,
                'user_queries' => 0,
            ]);
    }

    public function test_nothing_is_returned_when_oauth_throws_exception()
    {
        Exceptions::fake();

        $this->withToken('invalid')
            ->getJson('/guard')
            ->assertJson(['user' => null, 'client' => null]);

        Exceptions::assertReported(OAuthServerException::class);
        Exceptions::assertReportedCount(1);
    }

    public function test_null_is_returned_if_no_user_is_found()
    {
        $user = UserFactory::new()->create();
        ClientFactory::new()->asPersonalAccessTokenClient()->create();
        $token = $user->createToken('test')->accessToken;

        $user->delete();

        $this->withToken($token)
            ->getJson('/guard')
            ->assertJsonPath('user', null);
    }

    public function test_null_is_returned_for_client_credentials_token()
    {
        $client = ClientFactory::new()->asClientCredentials()->create();

        $token = $this->post('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
        ])->json('access_token');

        $this->withToken($token)
            ->getJson('/guard')
            ->assertJsonPath('user', null)
            ->assertJsonPath('client', $client->getKey());
    }

    public function test_null_is_returned_if_no_client_is_found()
    {
        $user = UserFactory::new()->create();
        $client = ClientFactory::new()->asPersonalAccessTokenClient()->create();
        $token = $user->createToken('test')->accessToken;

        $client->forceFill(['revoked' => true])->save();

        // Client lookups are memoized, so simulate the revocation happening between requests.
        Once::flush();

        $this->withToken($token)
            ->getJson('/guard')
            ->assertJson(['user' => null, 'client' => null]);
    }
}
