<?php

namespace Laravel\Passport\Tests\Feature;

use Laravel\Passport\Client;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class TokenRevocationTest extends PassportTestCase
{
    use WithLaravelMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Passport::tokensCan([
            'create' => 'Create',
            'read' => 'Read',
            'update' => 'Update',
            'delete' => 'Delete',
        ]);

        Passport::authorizationView(fn ($params) => $params);
    }

    public function testRevokeAccessToken()
    {
        $client = ClientFactory::new()->create();

        $token = $this->requestToken($client);

        $requestData = [
            'token' => $token['access_token'],
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
        ];

        $this->assertTrue($this->post('/oauth/introspect', $requestData)->assertOk()->json('active'));

        $this->post('/oauth/revoke', $requestData)->assertOk();

        $this->assertFalse($this->post('/oauth/introspect', $requestData)->assertOk()->json('active'));

        $requestData['token'] = $token['refresh_token'];

        $this->assertTrue($this->post('/oauth/introspect', $requestData)->assertOk()->json('active'));
    }

    public function testRevokeRefreshToken()
    {
        $client = ClientFactory::new()->create();

        $token = $this->requestToken($client);

        $requestData = [
            'token' => $token['refresh_token'],
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
        ];

        $this->assertTrue($this->post('/oauth/introspect', $requestData)->assertOk()->json('active'));

        $this->post('/oauth/revoke', $requestData)->assertOk();

        $this->assertFalse($this->post('/oauth/introspect', $requestData)->assertOk()->json('active'));

        $requestData['token'] = $token['access_token'];

        $this->assertFalse($this->post('/oauth/introspect', $requestData)->assertOk()->json('active'));
    }

    public function testInvalidClient(): void
    {
        $client1 = ClientFactory::new()->create();
        $client2 = ClientFactory::new()->create();

        $token = $this->requestToken($client1);

        $this->post('/oauth/revoke', [
            'token' => $token['access_token'],
            'client_id' => $client2->getKey(),
            'client_secret' => $client2->plainSecret,
        ])->assertOk();

        $this->post('/oauth/revoke', [
            'token' => $token['refresh_token'],
            'client_id' => $client2->getKey(),
            'client_secret' => $client2->plainSecret,
        ])->assertOk();

        $this->assertTrue($this->post('/oauth/introspect', [
            'token' => $token['access_token'],
            'client_id' => $client1->getKey(),
            'client_secret' => $client1->plainSecret,
        ])->assertOk()->json('active'));

        $this->assertTrue($this->post('/oauth/introspect', [
            'token' => $token['refresh_token'],
            'client_id' => $client1->getKey(),
            'client_secret' => $client1->plainSecret,
        ])->assertOk()->json('active'));
    }

    private function requestToken(Client $client)
    {
        $this->actingAs(UserFactory::new()->create(), 'web');

        $authToken = $this->get('/oauth/authorize?'.http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirect = $client->redirect_uris[0],
            'response_type' => 'code',
            'scope' => 'create read delete',
        ]))->assertOk()->json('authToken');

        $redirectUrl = $this->post('/oauth/authorize', ['auth_token' => $authToken])->headers->get('Location');
        parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $params);

        return $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'redirect_uri' => $redirect,
            'code' => $params['code'],
        ])->assertOK()->json();
    }
}
