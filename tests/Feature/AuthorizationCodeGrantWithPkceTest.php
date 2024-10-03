<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class AuthorizationCodeGrantWithPkceTest extends PassportTestCase
{
    use WithLaravelMigrations;

    protected function setUp(): void
    {
        PassportTestCase::setUp();

        Passport::tokensCan([
            'create' => 'Create',
            'read' => 'Read',
            'update' => 'Update',
            'delete' => 'Delete',
        ]);

        Passport::authorizationView(fn ($params) => $params);
    }

    public function testIssueAccessToken()
    {
        $client = ClientFactory::new()->asPublic()->create();

        $codeVerifier = Str::random(128);
        $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirect = $client->redirect_uris[0],
            'response_type' => 'code',
            'scope' => 'create read',
            'state' => $state = Str::random(40),
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        $user = UserFactory::new()->create();
        $this->actingAs($user, 'web');

        $response = $this->get('/oauth/authorize?'.$query);

        $response->assertOk();
        $response->assertSessionHas('authRequest');
        $response->assertSessionHas('authToken');
        $json = $response->json();
        $this->assertEqualsCanonicalizing(['client', 'user', 'scopes', 'request', 'authToken'], array_keys($json));
        $this->assertSame(collect(Passport::scopesFor(['create', 'read']))->toArray(), $json['scopes']);

        $response = $this->post('/oauth/authorize', ['auth_token' => $json['authToken']]);
        $response->assertRedirect();
        $response->assertSessionMissing(['authRequest', 'authToken']);

        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $params);

        $this->assertStringStartsWith($redirect.'?', $location);
        $this->assertSame($state, $params['state']);
        $this->assertArrayHasKey('code', $params);

        $response = $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirect,
            'code' => $params['code'],
            'code_verifier' => $codeVerifier,
        ]);

        $response->assertOk();
        $json = $response->json();
        $this->assertArrayHasKey('access_token', $json);
        $this->assertArrayHasKey('refresh_token', $json);
        $this->assertSame('Bearer', $json['token_type']);
        $this->assertSame(31536000, $json['expires_in']);

        Route::get('/foo', fn (Request $request) => $request->user()->token()->toJson())
            ->middleware('auth:api');

        $json = $this->withToken($json['access_token'], $json['token_type'])->get('/foo')->json();

        $this->assertSame($client->getKey(), $json['oauth_client_id']);
        $this->assertEquals($user->getAuthIdentifier(), $json['oauth_user_id']);
        $this->assertSame(['create', 'read'], $json['oauth_scopes']);
    }

    public function testRequireCodeChallenge()
    {
        $client = ClientFactory::new()->asPublic()->create();

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $client->redirect_uris[0],
            'response_type' => 'code',
        ]);

        $user = UserFactory::new()->create();
        $this->actingAs($user, 'web');
        $response = $this->get('/oauth/authorize?'.$query);

        $response->assertStatus(400);
        $json = $response->json();

        $this->assertSame('invalid_request', $json['error']);
        $this->assertSame('Code challenge must be provided for public clients', $json['hint']);
        $this->assertArrayHasKey('error_description', $json);
    }
}
