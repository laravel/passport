<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Contracts\Hashing\Hasher;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class ClientControllerTest extends PassportTestCase
{
    use WithLaravelMigrations;

    public function testCreatingClient()
    {
        $user = UserFactory::new()->create([
            'email' => 'foo@gmail.com',
            'password' => $this->app->make(Hasher::class)->make('foobar123'),
        ]);

        $this->actingAs($user);

        $response = $this->post(
            '/oauth/clients',
            [
                'name' => 'new client',
                'redirect_uris' => ['https://localhost'],
            ]
        );

        $response->assertSuccessful();

        $decodedResponse = $response->json();

        $this->assertEquals(1, $decodedResponse['user_id']);
        $this->assertEquals('new client', $decodedResponse['name']);
        $this->assertEquals(['https://localhost'], $decodedResponse['redirect_uris']);
        $this->assertArrayHasKey('secret', $decodedResponse);
        $this->assertArrayHasKey('provider', $decodedResponse);
        $this->assertArrayHasKey('revoked', $decodedResponse);
    }

    public function testUpdatingClient()
    {
        $user = UserFactory::new()->create([
            'email' => 'foo@gmail.com',
            'password' => $this->app->make(Hasher::class)->make('foobar123'),
        ]);

        $this->actingAs($user);

        $client = $this->post('/oauth/clients', [
            'name' => 'new client', 'redirect_uris' => ['https://localhost'],
        ])->json();

        $response = $this->put(
            '/oauth/clients/'.$client['id'],
            [
                'name' => 'updated client',
                'redirect_uris' => ['https://localhost'],
            ]
        );

        $response->assertSuccessful();

        $decodedResponse = $response->json();

        $this->assertEquals(1, $decodedResponse['user_id']);
        $this->assertEquals('updated client', $decodedResponse['name']);
        $this->assertEquals(['https://localhost'], $decodedResponse['redirect_uris']);
        $this->assertArrayHasKey('provider', $decodedResponse);
        $this->assertArrayHasKey('revoked', $decodedResponse);
    }
}
