<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Client;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Passport;
use Laravel\Passport\PersonalAccessTokenResult;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class PersonalAccessTokenFactoryTest extends PassportTestCase
{
    use WithLaravelMigrations;

    public function testIssueToken()
    {
        $user = UserFactory::new()->create([
            'email' => 'foo@gmail.com',
            'password' => $this->app->make(Hasher::class)->make('foobar123'),
        ]);

        /** @var Client $client */
        $client = ClientFactory::new()->asPersonalAccessTokenClient()->create(['scopes' => ['foo', 'bar']]);

        config([
            'passport.personal_access_client.id' => $client->getKey(),
            'passport.personal_access_client.secret' => $client->plainSecret,
        ]);

        Passport::tokensCan([
            'foo' => 'Do foo',
            'bar' => 'Do bar',
        ]);

        $result = $user->createToken('test', ['bar']);

        $this->assertInstanceOf(PersonalAccessTokenResult::class, $result);
        $this->assertSame($client->getKey(), $result->token->client_id);
        $this->assertSame($user->getAuthIdentifier(), $result->token->user_id);
        $this->assertSame(['bar'], $result->token->scopes);
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
            'passport.personal_access_client' => ['id' => $client->getKey(), 'secret' => $client->plainSecret],
            'passport.personal_access_client.admins' => ['id' => $adminClient->getKey(), 'secret' => $adminClient->plainSecret],
            'passport.personal_access_client.customers' => ['id' => $customerClient->getKey(), 'secret' => $customerClient->plainSecret],
        ]);

        $user = UserFactory::new()->create();
        $userToken = $user->createToken('test user');

        $admin = new AdminProviderStub;
        $adminToken = $admin->createToken('test admin');

        $customer = new CustomerProviderStub;
        $customerToken = $customer->createToken('test customer');

        $this->assertInstanceOf(PersonalAccessTokenResult::class, $userToken);
        $this->assertSame($client->getKey(), $userToken->token->client_id);
        $this->assertSame($user->getAuthIdentifier(), $userToken->token->user_id);

        $this->assertInstanceOf(PersonalAccessTokenResult::class, $adminToken);
        $this->assertSame($adminClient->getKey(), $adminToken->token->client_id);
        $this->assertSame($admin->getAuthIdentifier(), $adminToken->token->user_id);

        $this->assertInstanceOf(PersonalAccessTokenResult::class, $customerToken);
        $this->assertSame($customerClient->getKey(), $customerToken->token->client_id);
        $this->assertSame($customer->getAuthIdentifier(), $customerToken->token->user_id);

        DB::enableQueryLog();
        $userTokens = $user->tokens()->pluck('id')->all();
        $adminTokens = $admin->tokens()->pluck('id')->all();
        $customerTokens = $customer->tokens()->pluck('id')->all();
        DB::disableQueryLog();

        $queries = DB::getRawQueryLog();
        $this->assertStringContainsString('and ("provider" is null or "provider" = \'users\')', $queries[0]['raw_query']);
        $this->assertStringContainsString('and ("provider" = \'admins\')', $queries[1]['raw_query']);
        $this->assertStringContainsString('and ("provider" = \'customers\')', $queries[2]['raw_query']);

        $this->assertEquals([$userToken->token->id], $userTokens);
        $this->assertEquals([$adminToken->token->id], $adminTokens);
        $this->assertEquals([$customerToken->token->id], $customerTokens);
    }
}

class AdminProviderStub extends Authenticatable
{
    use HasApiTokens;

    protected $attributes = ['id' => 1];
}

class CustomerProviderStub extends Authenticatable
{
    use HasApiTokens;

    protected $attributes = ['id' => 3];
}
