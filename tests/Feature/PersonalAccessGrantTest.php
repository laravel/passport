<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Client;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Passport;
use Laravel\Passport\PersonalAccessTokenResult;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class PersonalAccessGrantTest extends PassportTestCase
{
    use WithLaravelMigrations;

    public function testIssueToken()
    {
        $user = UserFactory::new()->create();

        /** @var Client $client */
        $client = ClientFactory::new()->asPersonalAccessTokenClient()->create();

        Passport::tokensCan([
            'foo' => 'Do foo',
            'bar' => 'Do bar',
        ]);

        $result = $user->createToken('test', ['bar']);
        $token = $result->getToken();

        $this->assertInstanceOf(PersonalAccessTokenResult::class, $result);
        $this->assertArrayHasKey('accessToken', $result->toArray());
        $this->assertSame($token->getKey(), $result->accessTokenId);
        $this->assertSame('Bearer', $result->tokenType);
        $this->assertSame(31536000, $result->expiresIn);
        $this->assertSame($client->getKey(), $token->client_id);
        $this->assertSame($user->getAuthIdentifier(), $token->user_id);
        $this->assertSame(['bar'], $token->scopes);
        $this->assertSame('test', $token->name);

        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $token->id,
            'user_id' => $token->user_id,
            'client_id' => $token->client_id,
            'name' => $token->name,
        ]);
    }

    public function testIssueTokenWithAllScopes()
    {
        $user = UserFactory::new()->create();

        /** @var Client $client */
        $client = ClientFactory::new()->asPersonalAccessTokenClient()->create();

        $result = $user->createToken('test', ['*']);
        $token = $result->getToken();

        $this->assertInstanceOf(PersonalAccessTokenResult::class, $result);
        $this->assertSame($client->getKey(), $token->client_id);
        $this->assertSame($user->getAuthIdentifier(), $token->user_id);
        $this->assertSame(['*'], $token->scopes);
        $this->assertSame('test', $token->name);

        Route::get('/foo', fn (Request $request) => $request->user()->currentAccessToken()->toJson())
            ->middleware('auth:api');

        $json = $this->withToken($result->accessToken)->get('/foo')->json();

        $this->assertSame($token->getKey(), $json['oauth_access_token_id']);
        $this->assertSame($client->getKey(), $json['oauth_client_id']);
        $this->assertEquals($user->getAuthIdentifier(), $json['oauth_user_id']);
        $this->assertSame(['*'], $json['oauth_scopes']);
    }

    public function testIssueTokenWithDifferentProviders()
    {
        $client = ClientFactory::new()->asPersonalAccessTokenClient()->create();
        $adminClient = ClientFactory::new()->asPersonalAccessTokenClient()->create(['provider' => 'admins']);
        $customerClient = ClientFactory::new()->asPersonalAccessTokenClient()->create(['provider' => 'customers']);

        config([
            'auth.providers.admins' => ['driver' => 'eloquent', 'model' => AdminProviderStub::class],
            'auth.guards.api-admins' => ['driver' => 'passport', 'provider' => 'admins'],
            'auth.providers.customers' => ['driver' => 'eloquent', 'model' => CustomerProviderStub::class],
            'auth.guards.api-customers' => ['driver' => 'passport', 'provider' => 'customers'],
        ]);

        $user = UserFactory::new()->create();
        $userToken = $user->createToken('test user');
        $userTokenRecord = $userToken->getToken();

        $admin = new AdminProviderStub;
        $adminToken = $admin->createToken('test admin');
        $adminTokenRecord = $adminToken->getToken();

        $customer = new CustomerProviderStub;
        $customerToken = $customer->createToken('test customer');
        $customerTokenRecord = $customerToken->getToken();

        $this->assertInstanceOf(PersonalAccessTokenResult::class, $userToken);
        $this->assertSame($client->getKey(), $userTokenRecord->client_id);
        $this->assertSame($user->getAuthIdentifier(), $userTokenRecord->user_id);
        $this->assertSame('test user', $userTokenRecord->name);

        $this->assertInstanceOf(PersonalAccessTokenResult::class, $adminToken);
        $this->assertSame($adminClient->getKey(), $adminTokenRecord->client_id);
        $this->assertSame($admin->getAuthIdentifier(), $adminTokenRecord->user_id);
        $this->assertSame('test admin', $adminTokenRecord->name);

        $this->assertInstanceOf(PersonalAccessTokenResult::class, $customerToken);
        $this->assertSame($customerClient->getKey(), $customerTokenRecord->client_id);
        $this->assertSame($customer->getAuthIdentifier(), $customerTokenRecord->user_id);
        $this->assertSame('test customer', $customerTokenRecord->name);

        DB::enableQueryLog();
        $userTokens = $user->tokens()->pluck('id')->all();
        $adminTokens = $admin->tokens()->pluck('id')->all();
        $customerTokens = $customer->tokens()->pluck('id')->all();
        DB::disableQueryLog();

        $queries = DB::getRawQueryLog();
        $this->assertStringContainsString('and ("provider" is null or "provider" = \'users\')', $queries[0]['raw_query']);
        $this->assertStringContainsString('and ("provider" = \'admins\')', $queries[1]['raw_query']);
        $this->assertStringContainsString('and ("provider" = \'customers\')', $queries[2]['raw_query']);

        $this->assertEquals([$userToken->accessTokenId], $userTokens);
        $this->assertEquals([$adminToken->accessTokenId], $adminTokens);
        $this->assertEquals([$customerToken->accessTokenId], $customerTokens);
    }

    public function testPersonalAccessTokenRequestIsDisabled()
    {
        $user = UserFactory::new()->create();
        $client = ClientFactory::new()->asPersonalAccessTokenClient()->create();

        $response = $this->post('/oauth/token', [
            'grant_type' => 'personal_access',
            'provider' => $user->getProviderName(),
            'user_id' => $user->getKey(),
            'scope' => '',
        ]);

        $response->assertStatus(400);
        $json = $response->json();

        $this->assertSame('unsupported_grant_type', $json['error']);
        $this->assertArrayHasKey('error_description', $json);
        $this->assertArrayNotHasKey('access_token', $json);

        $this->assertInstanceOf(PersonalAccessTokenResult::class, $user->createToken('test'));
    }
}

class AdminProviderStub extends Authenticatable implements OAuthenticatable
{
    use HasApiTokens;

    protected $attributes = ['id' => 1];
}

class CustomerProviderStub extends Authenticatable implements OAuthenticatable
{
    use HasApiTokens;

    protected $attributes = ['id' => 3];
}
