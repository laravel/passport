<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Contracts\Hashing\Hasher;
use Laravel\Passport\Client;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\Passport;
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
        $client = ClientFactory::new()->asPersonalAccessTokenClient()->create();

        config([
            'passport.personal_access_client.id' => $client->getKey(),
            'passport.personal_access_client.secret' => $client->plainSecret,
        ]);

        Passport::tokensCan([
            'foo' => 'Do foo',
            'bar' => 'Do bar',
        ]);

        $token = $user->createToken('test', ['bar'])->token;

        $this->assertSame($client->getKey(), $token->client_id);
        $this->assertSame($user->getAuthIdentifier(), $token->user_id);
        $this->assertSame(['bar'], $token->scopes);
    }
}
