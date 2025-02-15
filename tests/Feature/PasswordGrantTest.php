<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class PasswordGrantTest extends PassportTestCase
{
    use WithLaravelMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Passport::enablePasswordGrant();

        Passport::tokensCan([
            'create' => 'Create',
            'read' => 'Read',
            'update' => 'Update',
            'delete' => 'Delete',
        ]);
    }

    public function testIssueToken()
    {
        $client = ClientFactory::new()->asPasswordClient()->create();
        $user = UserFactory::new()->create();

        $json = $this->post('/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'username' => $user->email,
            'password' => 'password',
            'scope' => 'create delete',
        ])->assertOk()->json();

        $this->assertArrayHasKey('access_token', $json);
        $this->assertArrayHasKey('refresh_token', $json);
        $this->assertSame('Bearer', $json['token_type']);
        $this->assertSame(31536000, $json['expires_in']);

        Route::get('/foo', fn (Request $request) => $request->user()->token()->toJson())
            ->middleware('auth:api');

        $json = $this->withToken($json['access_token'], $json['token_type'])->get('/foo')->json();

        $this->assertSame($client->getKey(), $json['oauth_client_id']);
        $this->assertEquals($user->getAuthIdentifier(), $json['oauth_user_id']);
        $this->assertSame(['create', 'delete'], $json['oauth_scopes']);
    }

    public function testIssueTokenWithAllScopes()
    {
        $client = ClientFactory::new()->asPasswordClient()->create();
        $user = UserFactory::new()->create();

        $json = $this->post('/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'username' => $user->email,
            'password' => 'password',
            'scope' => '*',
        ])->assertOk()->json();

        $this->assertArrayHasKey('access_token', $json);
        $this->assertArrayHasKey('refresh_token', $json);
        $this->assertSame('Bearer', $json['token_type']);
        $this->assertSame(31536000, $json['expires_in']);

        Route::get('/foo', fn (Request $request) => $request->user()->token()->toJson())
            ->middleware('auth:api');

        $json = $this->withToken($json['access_token'], $json['token_type'])->get('/foo')->json();

        $this->assertSame($client->getKey(), $json['oauth_client_id']);
        $this->assertEquals($user->getAuthIdentifier(), $json['oauth_user_id']);
        $this->assertSame(['*'], $json['oauth_scopes']);
    }

    public function testIssueTokenWithDifferentProviders()
    {
        $client = ClientFactory::new()->asPasswordClient()->create();
        $adminClient = ClientFactory::new()->asPasswordClient()->create(['provider' => 'admins']);

        config([
            'auth.providers.admins' => ['driver' => 'eloquent', 'model' => AdminProviderPasswordStub::class],
            'auth.guards.api-admins' => ['driver' => 'passport', 'provider' => 'admins'],
        ]);

        Schema::create('admin_provider_password_stubs', function ($table) {
            $table->id();
            $table->string('email');
            $table->string('password');
        });

        $user = UserFactory::new()->create();
        $admin = AdminProviderPasswordStub::query()->create([
            'email' => 'admin@example.org',
            'password' => 'admin-password',
        ]);

        $json = $this->post('/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'username' => $user->email,
            'password' => 'password',
        ])->assertOk()->json();

        Route::get('/foo', fn (Request $request) => response()->json([
            'user' => $request->user(),
            'token' => $request->user()->token(),
        ]))->middleware('auth:api');

        $json = $this->withToken($json['access_token'], $json['token_type'])->get('/foo')->json();

        $this->assertSame($user->getAuthIdentifier(), $json['user']['id']);
        $this->assertSame($user->email, $json['user']['email']);
        $this->assertSame($client->getKey(), $json['token']['oauth_client_id']);
        $this->assertEquals($user->getAuthIdentifier(), $json['token']['oauth_user_id']);

        $json = $this->post('/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $adminClient->getKey(),
            'client_secret' => $adminClient->plainSecret,
            'username' => $admin->email,
            'password' => 'admin-password',
        ])->assertOk()->json();

        Route::get('/bar', fn (Request $request) => response()->json([
            'user' => $request->user(),
            'token' => $request->user()->token(),
        ]))->middleware('auth:api-admins');

        $json = $this->withToken($json['access_token'], $json['token_type'])->get('/bar')->json();

        $this->assertSame($admin->getAuthIdentifier(), $json['user']['id']);
        $this->assertSame($admin->email, $json['user']['email']);
        $this->assertSame($adminClient->getKey(), $json['token']['oauth_client_id']);
        $this->assertEquals($admin->getAuthIdentifier(), $json['token']['oauth_user_id']);
    }

    public function testPublicClient()
    {
        $client = ClientFactory::new()->asPasswordClient()->asPublic()->create();
        $user = UserFactory::new()->create();

        $json = $this->post('/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $client->getKey(),
            'username' => $user->email,
            'password' => 'password',
        ])->assertOk()->json();

        $this->assertArrayHasKey('access_token', $json);
        $this->assertArrayHasKey('refresh_token', $json);
        $this->assertSame('Bearer', $json['token_type']);
        $this->assertSame(31536000, $json['expires_in']);
    }

    public function testUnauthorizedClient()
    {
        $client = ClientFactory::new()->create();
        $user = UserFactory::new()->create();

        $json = $this->post('/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'username' => $user->email,
            'password' => 'password',
        ])->assertBadRequest()->json();

        $this->assertSame('unauthorized_client', $json['error']);
        $this->assertSame(
            'The authenticated client is not authorized to use this authorization grant type.',
            $json['error_description']
        );
    }
}

class AdminProviderPasswordStub extends Authenticatable
{
    use HasApiTokens;

    public $timestamps = false;

    protected $guarded = false;

    protected $casts = [
        'password' => 'hashed',
    ];
}
