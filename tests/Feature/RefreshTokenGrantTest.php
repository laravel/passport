<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
        $json = $this->refreshToken()
            ->assertStatus(400)
            ->json();

        $this->assertSame('invalid_grant', $json['error']);
        $this->assertSame('The refresh token is invalid.', $json['error_description']);
        $this->assertSame('Token has been revoked', $json['hint']);
    }

    public function testRefreshingTokenWithoutRevoking()
    {
        Passport::$revokeRefreshTokens = false;

        $json = $this->refreshToken()
            ->assertOk()
            ->json();

        $this->assertArrayHasKey('access_token', $json);
        $this->assertArrayHasKey('refresh_token', $json);
        $this->assertSame(31536000, $json['expires_in']);
        $this->assertSame('Bearer', $json['token_type']);
    }

    private function refreshToken()
    {
        $client = ClientFactory::new()->create();

        $this->actingAs(UserFactory::new()->create(), 'web');

        $authToken = $this->get('/oauth/authorize?'.http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirect = $client->redirect_uris[0],
            'response_type' => 'code',
        ]))->json('authToken');

        $redirectUrl = $this->post('/oauth/authorize', ['auth_token' => $authToken])->headers->get('Location');
        parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $params);

        $oldToken = $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'redirect_uri' => $redirect,
            'code' => $params['code'],
        ])->assertOK()->json();

        $newToken = $this->post('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'refresh_token' => $oldToken['refresh_token'],
        ])->assertOK()->json();

        $this->assertArrayHasKey('access_token', $newToken);
        $this->assertArrayHasKey('refresh_token', $newToken);
        $this->assertSame(31536000, $newToken['expires_in']);
        $this->assertSame('Bearer', $newToken['token_type']);

        Route::get('/foo', fn (Request $request) => $request->user()->token()->toJson())->middleware('auth:api');

        $this->getJson('/foo', [
            'Authorization' => $oldToken['token_type'].' '.$oldToken['access_token'],
        ])->assertUnauthorized();

        $this->getJson('/foo', [
            'Authorization' => $newToken['token_type'].' '.$newToken['access_token'],
        ])->assertOk();

        return $this->post('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'refresh_token' => $oldToken['refresh_token'],
        ]);
    }
}
