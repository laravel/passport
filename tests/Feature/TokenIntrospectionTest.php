<?php

namespace Laravel\Passport\Tests\Feature;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\Client;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class TokenIntrospectionTest extends PassportTestCase
{
    use WithLaravelMigrations;

    protected function setUp(): void
    {
        Passport::enableTokenIntrospection();

        parent::setUp();

        Passport::tokensCan([
            'create' => 'Create',
            'read' => 'Read',
            'update' => 'Update',
            'delete' => 'Delete',
        ]);

        Passport::authorizationView(fn ($params) => $params);
    }

    public function testIntrospectToken()
    {
        $client = ClientFactory::new()->create();
        $user = UserFactory::new()->create();

        $token = $this->requestToken($user, $client);

        $json = $this->post('/oauth/introspect', [
            'token' => $token['access_token'],
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
        ])->assertOk()->json();

        $jwt = JWT::decode($token['access_token'], new Key(file_get_contents(self::PUBLIC_KEY), 'RS256'));

        $this->assertTrue($json['active']);
        $this->assertSame('create read delete', $json['scope']);
        $this->assertSame($client->getKey(), $json['client_id']);
        $this->assertSame('Bearer', $json['token_type']);
        $this->assertEquals($user->getAuthIdentifier(), $json['sub']);
        $this->assertArrayHasKey('aud', $json);
        $this->assertSame($jwt->jti, $json['jti']);
        $this->assertSame((int) $jwt->exp, $json['exp']);
        $this->assertSame((int) $jwt->iat, $json['iat']);
        $this->assertSame((int) $jwt->nbf, $json['nbf']);

        $json = $this->post('/oauth/introspect', [
            'token' => $token['refresh_token'],
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
        ])->assertOk()->json();

        $this->assertTrue($json['active']);
        $this->assertSame('create read delete', $json['scope']);
        $this->assertSame($client->getKey(), $json['client_id']);
        $this->assertEquals($user->getAuthIdentifier(), $json['sub']);
        $this->assertArrayHasKey('jti', $json);
        $this->assertEqualsWithDelta(31536000, $json['exp'] - time(), 5);
    }

    public function testInvalidClient(): void
    {
        $client1 = ClientFactory::new()->create();
        $client2 = ClientFactory::new()->create();
        $user = UserFactory::new()->create();

        $token = $this->requestToken($user, $client1);

        $this->assertFalse($this->post('/oauth/introspect', [
            'token' => $token['access_token'],
            'client_id' => $client2->getKey(),
            'client_secret' => $client2->plainSecret,
        ])->assertOk()->json('active'));

        $this->assertTrue($this->post('/oauth/introspect', [
            'token' => $token['access_token'],
            'client_id' => $client1->getKey(),
            'client_secret' => $client1->plainSecret,
        ])->assertOk()->json('active'));

        $this->assertFalse($this->post('/oauth/introspect', [
            'token' => $token['refresh_token'],
            'client_id' => $client2->getKey(),
            'client_secret' => $client2->plainSecret,
        ])->assertOk()->json('active'));

        $this->assertTrue($this->post('/oauth/introspect', [
            'token' => $token['refresh_token'],
            'client_id' => $client1->getKey(),
            'client_secret' => $client1->plainSecret,
        ])->assertOk()->json('active'));
    }

    private function requestToken(Authenticatable $user, Client $client)
    {
        $this->actingAs($user, 'web');

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
