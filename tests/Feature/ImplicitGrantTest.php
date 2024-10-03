<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class ImplicitGrantTest extends PassportTestCase
{
    use WithLaravelMigrations;

    protected function setUp(): void
    {
        PassportTestCase::setUp();

        Passport::enableImplicitGrant();

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
        $client = ClientFactory::new()->asImplicitClient()->create();

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirect = $client->redirect_uris[0],
            'response_type' => 'token',
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
        $response->assertSessionMissing(['authRequest', 'authToken']);

        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_FRAGMENT), $params);

        $this->assertStringStartsWith($redirect.'#', $location);
        $this->assertSame($state, $params['state']);
        $this->assertArrayHasKey('access_token', $params);
        $this->assertArrayNotHasKey('refresh_token', $params);
        $this->assertSame('Bearer', $params['token_type']);
        $this->assertSame('31536000', $params['expires_in']);

        Route::get('/foo', fn (Request $request) => $request->user()->token()->toJson())
            ->middleware('auth:api');

        $json = $this->withToken($params['access_token'], $params['token_type'])->get('/foo')->json();

        $this->assertSame($client->getKey(), $json['oauth_client_id']);
        $this->assertEquals($user->getAuthIdentifier(), $json['oauth_user_id']);
        $this->assertSame(['create', 'read'], $json['oauth_scopes']);
    }

    public function testDenyAuthorization()
    {
        $client = ClientFactory::new()->asImplicitClient()->create();

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirect = $client->redirect_uris[0],
            'response_type' => 'token',
            'scope' => 'create read',
            'state' => $state = Str::random(40),
        ]);

        $user = UserFactory::new()->create();
        $this->actingAs($user, 'web');
        $json = $this->get('/oauth/authorize?'.$query)->json();

        $response = $this->delete('/oauth/authorize', ['auth_token' => $json['authToken']]);
        $response->assertRedirect();
        $response->assertSessionMissing(['authRequest', 'authToken']);

        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_FRAGMENT), $params);

        // $this->assertStringStartsWith($redirect.'#', $location);
        // $this->assertSame($state, $params['state']);
        $this->assertSame('access_denied', $params['error']);
        $this->assertArrayHasKey('error_description', $params);
    }

    public function testSkipsAuthorizationWhenHasGrantedScopes()
    {
        $client = ClientFactory::new()->asImplicitClient()->create();

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirect = $client->redirect_uris[0],
            'response_type' => 'token',
            'scope' => 'create read',
            'state' => $state = Str::random(40),
        ]);

        $user = UserFactory::new()->create();
        $this->actingAs($user, 'web');
        $json = $this->get('/oauth/authorize?'.$query)->json();

        $response = $this->post('/oauth/authorize', ['auth_token' => $json['authToken']]);
        $response->assertRedirect();

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirect,
            'response_type' => 'token',
            'scope' => 'create',
            'state' => $state = Str::random(40),
        ]);

        $response = $this->get('/oauth/authorize?'.$query);
        $response->assertRedirect();
        $response->assertSessionMissing(['authRequest', 'authToken']);

        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_FRAGMENT), $params);

        $this->assertStringStartsWith($redirect.'#', $location);
        $this->assertSame($state, $params['state']);
        $this->assertArrayHasKey('access_token', $params);
        $this->assertArrayNotHasKey('refresh_token', $params);
        $this->assertSame('Bearer', $params['token_type']);
        $this->assertSame('31536000', $params['expires_in']);
    }

    public function testValidateAuthorizationRequest()
    {
        $client = ClientFactory::new()->asImplicitClient()->create();

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => fake()->url(),
            'response_type' => 'token',
        ]);

        $json = $this->get('/oauth/authorize?'.$query)->json();
        $this->assertSame('invalid_client', $json['error']);
        $this->assertArrayHasKey('error_description', $json);
    }

    public function testValidateScopes()
    {
        $client = ClientFactory::new()->asImplicitClient()->create();

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirect = $client->redirect_uris[0],
            'response_type' => 'token',
            'scope' => 'foo'
        ]);

        $response = $this->get('/oauth/authorize?'.$query);
        $response->assertRedirect();

        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_FRAGMENT), $params);

        $this->assertStringStartsWith($redirect.'#', $location);
        // $this->assertSame($state, $params['state']);
        $this->assertSame('invalid_scope', $params['error']);
        $this->assertArrayHasKey('error_description', $params);
    }

    public function testRedirectGuestUser()
    {
        Route::get('/foo', fn () => '')->name('login');

        $client = ClientFactory::new()->asImplicitClient()->create();

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $client->redirect_uris[0],
            'response_type' => 'token',
        ]);

        $response = $this->get('/oauth/authorize?'.$query);
        $response->assertSessionHas('promptedForLogin', true);
        $response->assertRedirectToRoute('login');
    }

    public function testPromptNone()
    {
        $client = ClientFactory::new()->asImplicitClient()->create();

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirect = $client->redirect_uris[0],
            'response_type' => 'token',
            'state' => $state = Str::random(40),
            'prompt' => 'none',
        ]);

        $this->actingAs(UserFactory::new()->create(), 'web');
        $response = $this->get('/oauth/authorize?'.$query);
        $response->assertRedirect();

        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_FRAGMENT), $params);

        $this->assertStringStartsWith($redirect.'#', $location);
        $this->assertSame($state, $params['state']);
        $this->assertSame('consent_required', $params['error']);
        $this->assertArrayHasKey('error_description', $params);
    }

    public function testPromptNoneLoginRequired()
    {
        $client = ClientFactory::new()->asImplicitClient()->create();

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirect = $client->redirect_uris[0],
            'response_type' => 'token',
            'state' => $state = Str::random(40),
            'prompt' => 'none',
        ]);

        $response = $this->get('/oauth/authorize?'.$query);
        $response->assertRedirect();

        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_FRAGMENT), $params);

        $this->assertStringStartsWith($redirect.'#', $location);
        $this->assertSame($state, $params['state']);
        $this->assertSame('login_required', $params['error']);
        $this->assertArrayHasKey('error_description', $params);
    }

    public function testPromptConsent()
    {
        $client = ClientFactory::new()->asImplicitClient()->create();

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirect = $client->redirect_uris[0],
            'response_type' => 'token',
            'scope' => 'create read update',
            'state' => Str::random(40),
        ]);

        $user = UserFactory::new()->create();
        $this->actingAs($user, 'web');
        $json = $this->get('/oauth/authorize?'.$query)->json();

        $response = $this->post('/oauth/authorize', ['auth_token' => $json['authToken']]);
        $response->assertRedirect();

        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_FRAGMENT), $params);

        $this->assertStringStartsWith($redirect.'#', $location);
        $this->assertArrayHasKey('access_token', $params);

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $redirect,
            'response_type' => 'token',
            'scope' => 'create read',
            'state' => Str::random(40),
            'prompt' => 'consent',
        ]);

        $response = $this->get('/oauth/authorize?'.$query);

        $response->assertOk();
        $response->assertSessionHas('authRequest');
        $response->assertSessionHas('authToken');
        $json = $response->json();
        $this->assertEqualsCanonicalizing(['client', 'user', 'scopes', 'request', 'authToken'], array_keys($json));
        $this->assertSame(collect(Passport::scopesFor(['create', 'read']))->toArray(), $json['scopes']);
    }

    public function testPromptLogin()
    {
        Route::get('/foo', fn () => '')->name('login');

        $client = ClientFactory::new()->asImplicitClient()->create();

        $query = http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $client->redirect_uris[0],
            'response_type' => 'token',
            'scope' => 'create read update',
            'state' => Str::random(40),
            'prompt' => 'login',
        ]);

        $user = UserFactory::new()->create();
        $this->actingAs($user, 'web');
        $response = $this->get('/oauth/authorize?'.$query);

        $response->assertSessionHas('promptedForLogin', true);
        $response->assertRedirectToRoute('login');
    }
}
