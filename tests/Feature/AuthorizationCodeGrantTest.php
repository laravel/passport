<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class AuthorizationCodeGrantTest extends PassportTestCase
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

    public function testIssueAccessToken()
    {
        $client = ClientFactory::new()->create();

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirect = $client->redirect_uris[0],
            'response_type' => 'code',
            'scope' => 'create read',
            'state' => $state = Str::random(40),
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
        $response->assertSessionMissing(['deviceCode', 'authToken']);

        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $params);

        $this->assertStringStartsWith($redirect.'?', $location);
        $this->assertSame($state, $params['state']);
        $this->assertArrayHasKey('code', $params);

        $response = $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'redirect_uri' => $redirect,
            'code' => $params['code'],
        ]);

        $response->assertOk();
        $json = $response->json();
        $this->assertArrayHasKey('access_token', $json);
        $this->assertArrayHasKey('refresh_token', $json);
        $this->assertSame('Bearer', $json['token_type']);
        $this->assertSame(31536000, $json['expires_in']);

        Route::get('/foo', fn (Request $request) => $request->user()->token()->toJson())
            ->middleware('auth:api');

        $json = $this->get('/foo', [
            'Authorization' => 'Bearer '.$json['access_token'],
        ])->json();

        $this->assertSame($client->getKey(), $json['oauth_client_id']);
        $this->assertEquals($user->getAuthIdentifier(), $json['oauth_user_id']);
        $this->assertSame(['create', 'read'], $json['oauth_scopes']);
    }

    public function testDenyAuthorization()
    {
        $client = ClientFactory::new()->create();

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirect = $client->redirect_uris[0],
            'response_type' => 'code',
            'scope' => '',
            'state' => $state = Str::random(40),
        ]);

        $user = UserFactory::new()->create();
        $this->actingAs($user, 'web');
        $json = $this->get('/oauth/authorize?'.$query)->json();

        $response = $this->delete('/oauth/authorize', ['auth_token' => $json['authToken']]);
        $response->assertRedirect();
        $response->assertSessionMissing(['deviceCode', 'authToken']);

        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $params);

        $this->assertStringStartsWith($redirect.'?', $location);
        $this->assertSame($state, $params['state']);
        $this->assertSame('access_denied', $params['error']);
        $this->assertArrayHasKey('error_description', $params);
    }

    public function testSkipsAuthorizationWhenHasGrantedScopes()
    {
        $client = ClientFactory::new()->create();

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirect = $client->redirect_uris[0],
            'response_type' => 'code',
            'scope' => 'create read update',
            'state' => Str::random(40),
        ]);

        $user = UserFactory::new()->create();
        $this->actingAs($user, 'web');
        $json = $this->get('/oauth/authorize?'.$query)->json();

        $response = $this->post('/oauth/authorize', ['auth_token' => $json['authToken']]);
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $params);

        $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'redirect_uri' => $redirect,
            'code' => $params['code'],
        ]);

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirect,
            'response_type' => 'code',
            'scope' => 'create read',
            'state' => $state = Str::random(40),
        ]);

        $response = $this->get('/oauth/authorize?'.$query);
        $response->assertRedirect();

        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $params);

        $this->assertStringStartsWith($redirect.'?', $location);
        $this->assertSame($state, $params['state']);
        $this->assertArrayHasKey('code', $params);
    }

    public function testValidateAuthorizationRequest()
    {
        $client = ClientFactory::new()->create();

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => fake()->url(),
            'response_type' => 'code',
            'scope' => '',
            'state' => Str::random(40),
        ]);

        $json = $this->get('/oauth/authorize?'.$query)->json();
        $this->assertSame('invalid_client', $json['error']);
        $this->assertArrayHasKey('error_description', $json);
    }

    public function testRedirectGuestUser()
    {
        Route::get('/foo', fn () => '')->name('login');

        $client = ClientFactory::new()->create();

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $client->redirect_uris[0],
            'response_type' => 'code',
        ]);

        $response = $this->get('/oauth/authorize?'.$query);
        $response->assertSessionHas('promptedForLogin', true);
        $response->assertRedirectToRoute('login');
    }

    public function testPromptNone()
    {
        $client = ClientFactory::new()->create();

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirect = $client->redirect_uris[0],
            'response_type' => 'code',
            'state' => $state = Str::random(40),
            'prompt' => 'none',
        ]);

        $this->actingAs(UserFactory::new()->create(), 'web');
        $response = $this->get('/oauth/authorize?'.$query);
        $response->assertRedirect();

        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $params);

        $this->assertStringStartsWith($redirect.'?', $location);
        $this->assertSame($state, $params['state']);
        $this->assertSame('access_denied', $params['error']);
        $this->assertArrayHasKey('error_description', $params);
    }
}
