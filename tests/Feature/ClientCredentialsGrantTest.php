<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\Http\Middleware\CheckToken;
use Laravel\Passport\Http\Middleware\EnsureClientIsResourceOwner;
use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\App\Models\User;

class ClientCredentialsGrantTest extends PassportTestCase
{
    use WithLaravelMigrations;

    protected function setUp(): void
    {
        Passport::$clientCredentialsTokensExpireIn = null;

        parent::setUp();

        Passport::tokensCan([
            'create' => 'Create',
            'read' => 'Read',
            'update' => 'Update',
            'delete' => 'Delete',
        ]);
    }

    public function testIssueAccessToken()
    {
        $client = ClientFactory::new()->asClientCredentials()->create();

        $json = $this->post('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'scope' => 'create read delete',
        ])->assertOk()->json();

        $this->assertArrayHasKey('access_token', $json);
        $this->assertArrayNotHasKey('refresh_token', $json);
        $this->assertSame('Bearer', $json['token_type']);
        $this->assertEqualsWithDelta(31536000, $json['expires_in'], 2);

        Route::get('/foo', fn (Request $request) => response('response'))
            ->middleware([EnsureClientIsResourceOwner::using(['create', 'delete'])]);

        $response = $this->withToken($json['access_token'], $json['token_type'])->get('/foo');
        $response->assertOk();
        $this->assertSame('response', $response->content());

        Route::get('/bar', fn (Request $request) => response('response'))
            ->middleware(CheckToken::using(['create', 'update']));

        $response = $this->withToken($json['access_token'], $json['token_type'])->get('/bar');
        $response->assertForbidden();
    }

    public function testIssueAccessTokenWithAllScopes()
    {
        $client = ClientFactory::new()->asClientCredentials()->create();

        $json = $this->post('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'scope' => '*',
        ])->assertOk()->json();

        $this->assertArrayHasKey('access_token', $json);
        $this->assertArrayNotHasKey('refresh_token', $json);
        $this->assertSame('Bearer', $json['token_type']);
        $this->assertEqualsWithDelta(31536000, $json['expires_in'], 2);

        Route::get('/foo', fn (Request $request) => response('response'))
            ->middleware([EnsureClientIsResourceOwner::using(['create', 'delete'])]);

        $response = $this->withToken($json['access_token'], $json['token_type'])->get('/foo');
        $response->assertOk();

        Route::get('/bar', fn (Request $request) => response('response'))
            ->middleware(CheckToken::using(['create', 'delete']));

        $response = $this->withToken($json['access_token'], $json['token_type'])->get('/bar');
        $response->assertOk();
    }

    public function testClientCredentialsTokenDoesNotResolveUserWithMatchingId()
    {
        Passport::$clientUuids = false;

        // When the client ID and user ID are the same, the JWT "sub" claim
        // (set to the client ID for client_credentials tokens) could resolve
        // the wrong user through implicit type casting.
        $client = ClientFactory::new()->asClientCredentials()->create(['id' => 1]);

        User::forceCreate([
            'id' => 1,
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'password' => 'password',
        ]);

        $json = $this->post('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
        ])->assertOk()->json();

        Route::get('/foo', fn (Request $request) => response()->json([
            'user_id' => $request->user()?->getKey(),
        ]))->middleware('auth:api');

        $this->withToken($json['access_token'])->getJson('/foo')
            ->assertUnauthorized();

        Route::get('/bar', fn (Request $request) => response('response'))
            ->middleware(EnsureClientIsResourceOwner::class);

        $this->withToken($json['access_token'])->get('/bar')
            ->assertOk();

        Passport::$clientUuids = true;
    }

    public function testPublicClient()
    {
        $client = ClientFactory::new()->asClientCredentials()->asPublic()->create();

        $json = $this->post('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->getKey(),
        ])->assertUnauthorized()->json();

        $this->assertSame('invalid_client', $json['error']);
        $this->assertSame('Client authentication failed', $json['error_description']);
    }

    public function testUnauthorizedClient()
    {
        $client = ClientFactory::new()->create();

        $json = $this->post('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
        ])->assertBadRequest()->json();

        $this->assertSame('unauthorized_client', $json['error']);
        $this->assertSame(
            'The authenticated client is not authorized to use this authorization grant type.',
            $json['error_description']
        );
    }

    public function testCustomClientCredentialsTokenExpiration()
    {
        Passport::clientCredentialsTokensExpireIn(new \DateInterval('P7D'));

        $client = ClientFactory::new()->asClientCredentials()->create();

        $json = $this->post('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
        ])->assertOk()->json();

        // 7 days = 604800 seconds
        $this->assertEqualsWithDelta(604800, $json['expires_in'], 2);
    }
}
