<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class DeviceAuthorizationGrantTest extends PassportTestCase
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

        Passport::deviceAuthorizationView(fn ($params) => $params);
        Passport::deviceUserCodeView(fn ($params) => $params);
    }

    public function testIssueDeviceCode()
    {
        $client = ClientFactory::new()->asDeviceCodeClient()->create();

        $json = $this->post('/oauth/device/code', [
            'client_id' => $client->getKey(),
            'scope' => 'create read',
        ])->assertOk()->json();

        $this->assertArrayHasKey('device_code', $json);
        $this->assertArrayHasKey('user_code', $json);
        // $this->assertSame(5, $json['interval']); // TODO https://github.com/thephpleague/oauth2-server/pull/1410
        $this->assertSame(600, $json['expires_in']);
        $this->assertSame('http://localhost/oauth/device', $json['verification_uri']);
        $this->assertSame('http://localhost/oauth/device?user_code='.$json['user_code'], $json['verification_uri_complete']);
    }

    public function testRequestAccessTokenAuthorizationPending()
    {
        $client = ClientFactory::new()->asDeviceCodeClient()->create();

        ['device_code' => $deviceCode] = $this->post('/oauth/device/code', [
            'client_id' => $client->getKey(),
            'scope' => 'create read',
        ])->assertOk()->json();

        $json = $this->post('/oauth/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'device_code' => $deviceCode,
        ])->assertBadRequest()->json();

        $this->assertArrayHasKey('error', $json);
        $this->assertArrayHasKey('error_description', $json);
        $this->assertSame('authorization_pending', $json['error']);
    }

    public function testAuthorizationWithoutUserCodeRedirects()
    {
        $user = UserFactory::new()->create();

        $response = $this->actingAs($user)->get('/oauth/device/authorize');
        $response->assertRedirect('/oauth/device');
        $response->assertRedirectToRoute('passport.device');
    }

    public function testVerificationUrl()
    {
        $client = ClientFactory::new()->asDeviceCodeClient()->create();

        [
            'verification_uri' => $verificationUri,
            'verification_uri_complete' => $verificationUriComplete,
            'user_code' => $userCode,
        ] = $this->post('/oauth/device/code', [
            'client_id' => $client->getKey(),
            'scope' => 'create read',
        ])->assertOk()->json();

        $json = $this->get($verificationUri)->assertOk()->json();
        $this->assertEqualsCanonicalizing(['request'], array_keys($json));

        $user = UserFactory::new()->create();

        $response = $this->actingAs($user, 'web')->get($verificationUriComplete);
        $response->assertRedirect('/oauth/device/authorize?user_code='.$userCode);
        $response->assertRedirectToRoute('passport.device.authorizations.authorize', ['user_code' => $userCode]);
    }

    public function testAuthorizationWithInvalidUserCode()
    {
        $user = UserFactory::new()->create();

        $response = $this->actingAs($user, 'web')->get('/oauth/device/authorize?user_code=12345678');
        $response->assertRedirectToRoute('passport.device');
        $response->assertSessionHasInput('user_code', '12345678');
        $response->assertSessionHasErrors(['user_code' => 'Incorrect code.']);
    }

    public function testRequestAccessToken()
    {
        $client = ClientFactory::new()->asDeviceCodeClient()->create();

        [
            'device_code' => $deviceCode,
            'user_code' => $userCode,
        ] = $this->post('/oauth/device/code', [
            'client_id' => $client->getKey(),
            'scope' => 'create read',
        ])->assertOk()->json();

        $user = UserFactory::new()->create();
        $this->actingAs($user, 'web');

        $json = $this->get('/oauth/device/authorize?user_code='.$userCode)
            ->assertOk()
            ->assertSessionHas('deviceCode')
            ->assertSessionHas('authToken')
            ->json();
        $this->assertEqualsCanonicalizing(['client', 'user', 'scopes', 'request', 'authToken'], array_keys($json));
        $this->assertSame(collect(Passport::scopesFor(['create', 'read']))->toArray(), $json['scopes']);

        $response = $this->post('/oauth/device/authorize', ['auth_token' => $json['authToken']]);
        $response->assertRedirectToRoute('passport.device');
        $response->assertSessionHas('status', 'authorization-approved');
        $response->assertSessionMissing(['deviceCode', 'authToken']);

        $json = $this->post('/oauth/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'device_code' => $deviceCode,
        ])->assertOk()->json();

        $this->assertArrayHasKey('access_token', $json);
        $this->assertArrayHasKey('refresh_token', $json);
        $this->assertSame('Bearer', $json['token_type']);
        $this->assertSame(31536000, $json['expires_in']);

        Route::get('/foo', fn (Request $request) => $request->user()->token()->toJson())
            ->middleware('auth:api');

        $json = $this->withToken($json['access_token'], $json['token_type'])->get('/foo')->json();

        $this->assertSame($client->getKey(), $json['oauth_client_id']);
        $this->assertEquals($user->getAuthIdentifier(), $json['oauth_user_id']);
        // $this->assertSame(['create', 'read'], $json['oauth_scopes']); TODO: https://github.com/thephpleague/oauth2-server/pull/1412
    }

    public function testDenyAuthorization()
    {
        $client = ClientFactory::new()->asDeviceCodeClient()->create();

        [
            'device_code' => $deviceCode,
            'user_code' => $userCode,
        ] = $this->post('/oauth/device/code', [
            'client_id' => $client->getKey(),
            'scope' => 'create read',
        ])->assertOk()->json();

        $user = UserFactory::new()->create();
        $this->actingAs($user, 'web');

        $authToken = $this->get('/oauth/device/authorize?user_code='.$userCode)->assertOk()->json('authToken');

        $response = $this->delete('/oauth/device/authorize', ['auth_token' => $authToken]);
        $response->assertRedirectToRoute('passport.device');
        $response->assertSessionHas('status', 'authorization-denied');
        $response->assertSessionMissing(['deviceCode', 'authToken']);

        $json = $this->post('/oauth/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'device_code' => $deviceCode,
        ])->assertUnauthorized()->json();

        $this->assertArrayHasKey('error', $json);
        $this->assertArrayHasKey('error_description', $json);
        $this->assertSame('access_denied', $json['error']);
    }

    public function testRequestAccessTokenWithPublicClient()
    {
        $client = ClientFactory::new()->asDeviceCodeClient()->asPublic()->create();

        [
            'device_code' => $deviceCode,
            'user_code' => $userCode,
        ] = $this->post('/oauth/device/code', [
            'client_id' => $client->getKey(),
            'scope' => 'create read',
        ])->assertOk()->json();

        $user = UserFactory::new()->create();
        $this->actingAs($user, 'web');

        $authToken = $this->get('/oauth/device/authorize?user_code='.$userCode)->assertOk()->json('authToken');

        $this->post('/oauth/device/authorize', ['auth_token' => $authToken])->assertRedirect();

        $json = $this->post('/oauth/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            'client_id' => $client->getKey(),
            'device_code' => $deviceCode,
        ])->assertOk()->json();

        $this->assertArrayHasKey('access_token', $json);
        $this->assertArrayHasKey('refresh_token', $json);
        $this->assertSame('Bearer', $json['token_type']);
        $this->assertSame(31536000, $json['expires_in']);
    }
}
