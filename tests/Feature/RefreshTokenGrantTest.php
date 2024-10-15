<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Client;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class RefreshTokenGrantTest extends PassportTestCase
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

        Passport::$revokeRefreshTokens = true;

        Passport::authorizationView(fn ($params) => $params);
    }

    public function testRefreshingToken()
    {
        $client = ClientFactory::new()->create();

        $oldToken = $this->getNewAccessToken($client);

        $newToken = $this->post('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'refresh_token' => $oldToken['refresh_token'],
            'scope' => 'read delete',
        ])->assertOK()->json();

        $this->assertArrayHasKey('access_token', $newToken);
        $this->assertArrayHasKey('refresh_token', $newToken);
        $this->assertSame(31536000, $newToken['expires_in']);
        $this->assertSame('Bearer', $newToken['token_type']);

        Route::get('/foo', fn (Request $request) => $request->user()->token()->toJson())
            ->middleware('auth:api');

        $this->getJson('/foo', [
            'Authorization' => $oldToken['token_type'].' '.$oldToken['access_token'],
        ])->assertUnauthorized();

        $json = $this->getJson('/foo', [
            'Authorization' => $newToken['token_type'].' '.$newToken['access_token'],
        ])->assertOk()->json();

        $this->assertSame(['read', 'delete'], $json['oauth_scopes']);

        $json = $this->post('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'refresh_token' => $oldToken['refresh_token'],
        ])->assertStatus(400)->json();

        $this->assertSame('invalid_grant', $json['error']);
        $this->assertSame('The refresh token is invalid.', $json['error_description']);
        $this->assertSame('Token has been revoked', $json['hint']);
    }

    public function testRefreshingTokenWithoutRevoking()
    {
        Passport::$revokeRefreshTokens = false;

        $client = ClientFactory::new()->create();

        $oldToken = $this->getNewAccessToken($client);

        $newToken = $this->post('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'refresh_token' => $oldToken['refresh_token'],
            'scope' => 'read delete',
        ])->assertOK()->json();

        $this->assertArrayHasKey('access_token', $newToken);
        $this->assertArrayHasKey('refresh_token', $newToken);
        $this->assertSame(31536000, $newToken['expires_in']);
        $this->assertSame('Bearer', $newToken['token_type']);

        Route::get('/foo', fn (Request $request) => $request->user()->token()->toJson())
            ->middleware('auth:api');

        $this->getJson('/foo', [
            'Authorization' => $oldToken['token_type'].' '.$oldToken['access_token'],
        ])->assertUnauthorized();

        $json = $this->getJson('/foo', [
            'Authorization' => $newToken['token_type'].' '.$newToken['access_token'],
        ])->assertOk();

        $this->assertSame(['read', 'delete'], $json['oauth_scopes']);

        $json = $this->post('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'refresh_token' => $oldToken['refresh_token'],
        ])->assertOk()->json();

        $this->assertArrayHasKey('access_token', $json);
        $this->assertArrayHasKey('refresh_token', $json);
        $this->assertSame(31536000, $json['expires_in']);
        $this->assertSame('Bearer', $json['token_type']);
    }

    public function testRefreshingTokenWithAdditionalScopes()
    {
        $client = ClientFactory::new()->create();

        $oldToken = $this->getNewAccessToken($client);

        $json = $this->post('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'refresh_token' => $oldToken['refresh_token'],
            'scope' => 'create update',
        ])->assertStatus(400)->json();

        $this->assertSame('invalid_scope', $json['error']);
        $this->assertSame('The requested scope is invalid, unknown, or malformed', $json['error_description']);
        $this->assertSame('Check the `update` scope', $json['hint']);
    }

    private function getNewAccessToken(Client $client)
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
