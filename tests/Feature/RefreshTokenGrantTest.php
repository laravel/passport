<?php

namespace Laravel\Passport\Tests\Feature;

use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class RefreshTokenGrantTest extends PassportTestCase
{
    use WithLaravelMigrations;

    protected function setUp(): void
    {
        PassportTestCase::setUp();

        Passport::authorizationView(fn ($params) => $params);
    }

    public function testRefreshingToken()
    {
        $this->assertArrayHasKey('refresh_token', $this->refreshToken());
    }

    public function testRefreshingTokenWithoutRevoking()
    {
        Passport::$revokeRefreshTokens = false;

        $this->assertArrayNotHasKey('refresh_token', $this->refreshToken());
    }

    private function refreshToken()
    {
        $client = ClientFactory::new()->create();

        $this->actingAs(UserFactory::new()->create(), 'web');

        $json = $this->get('/oauth/authorize?'.http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirect = $client->redirect_uris[0],
            'response_type' => 'code',
        ]))->json();

        $redirectUrl = $this->post('/oauth/authorize', ['auth_token' => $json['authToken']])->headers->get('Location');
        parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $params);

        $json = $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'redirect_uri' => $redirect,
            'code' => $params['code'],
        ])->json();

        $response = $this->post('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'refresh_token' => $json['refresh_token'],
        ]);

        $response->assertOk();
        $json = $response->json();
        $this->assertArrayHasKey('access_token', $json);
        $this->assertSame(31536000, $json['expires_in']);
        $this->assertSame('Bearer', $json['token_type']);

        return $json;
    }
}
