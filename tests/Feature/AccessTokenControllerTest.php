<?php

namespace Laravel\Passport\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;
use Lcobucci\JWT\Configuration;

class AccessTokenControllerTest extends PassportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('password');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    protected function getUserClass()
    {
        return User::class;
    }

    public function testGettingAccessTokenWithClientCredentialsGrant()
    {
        $this->withoutExceptionHandling();

        $user = new User();
        $user->email = 'foo@gmail.com';
        $user->password = $this->app->make(Hasher::class)->make('foobar123');
        $user->save();

        /** @var Client $client */
        $client = ClientFactory::new()->asClientCredentials()->create(['user_id' => $user->id]);

        $response = $this->post(
            '/oauth/token',
            [
                'grant_type' => 'client_credentials',
                'client_id' => $client->id,
                'client_secret' => $client->secret,
            ]
        );

        $response->assertOk();

        $response->assertHeader('pragma', 'no-cache');
        $response->assertHeader('cache-control', 'no-store, private');
        $response->assertHeader('content-type', 'application/json; charset=UTF-8');

        $decodedResponse = $response->decodeResponseJson()->json();

        $this->assertArrayHasKey('token_type', $decodedResponse);
        $this->assertArrayHasKey('expires_in', $decodedResponse);
        $this->assertArrayHasKey('access_token', $decodedResponse);
        $this->assertSame('Bearer', $decodedResponse['token_type']);
        $expiresInSeconds = 31536000;
        $this->assertEqualsWithDelta($expiresInSeconds, $decodedResponse['expires_in'], 5);

        $jwtAccessToken = Configuration::forUnsecuredSigner()->parser()->parse($decodedResponse['access_token']);
        $this->assertTrue($this->app->make(ClientRepository::class)->findActive($jwtAccessToken->claims()->get('aud'))->is($client));

        $token = $this->app->make(TokenRepository::class)->find($jwtAccessToken->claims()->get('jti'));
        $this->assertInstanceOf(Token::class, $token);
        $this->assertTrue($token->client->is($client));
        $this->assertFalse($token->revoked);
        $this->assertNull($token->name);
        $this->assertNull($token->user_id);
        $this->assertLessThanOrEqual(5, CarbonImmutable::now()->addSeconds($expiresInSeconds)->diffInSeconds($token->expires_at));
    }

    public function testGettingAccessTokenWithClientCredentialsGrantInvalidClientSecret()
    {
        $user = new User();
        $user->email = 'foo@gmail.com';
        $user->password = $this->app->make(Hasher::class)->make('foobar123');
        $user->save();

        /** @var Client $client */
        $client = ClientFactory::new()->asClientCredentials()->create(['user_id' => $user->id]);

        $response = $this->post(
            '/oauth/token',
            [
                'grant_type' => 'client_credentials',
                'client_id' => $client->id,
                'client_secret' => $client->secret.'foo',
            ]
        );

        $response->assertStatus(401);

        $response->assertHeader('cache-control', 'no-cache, private');
        $response->assertHeader('content-type', 'application/json');

        $decodedResponse = $response->decodeResponseJson()->json();

        $this->assertArrayNotHasKey('token_type', $decodedResponse);
        $this->assertArrayNotHasKey('expires_in', $decodedResponse);
        $this->assertArrayNotHasKey('access_token', $decodedResponse);

        $this->assertArrayHasKey('error', $decodedResponse);
        $this->assertSame('invalid_client', $decodedResponse['error']);
        $this->assertArrayHasKey('error_description', $decodedResponse);
        $this->assertSame('Client authentication failed', $decodedResponse['error_description']);
        $this->assertArrayNotHasKey('hint', $decodedResponse);
        $this->assertArrayHasKey('message', $decodedResponse);
        $this->assertSame('Client authentication failed', $decodedResponse['message']);

        $this->assertSame(0, Token::count());
    }

    public function testGettingAccessTokenWithPasswordGrant()
    {
        $this->withoutExceptionHandling();

        $password = 'foobar123';
        $user = new User();
        $user->email = 'foo@gmail.com';
        $user->password = $this->app->make(Hasher::class)->make($password);
        $user->save();

        /** @var Client $client */
        $client = ClientFactory::new()->asPasswordClient()->create(['user_id' => $user->id]);

        $response = $this->post(
            '/oauth/token',
            [
                'grant_type' => 'password',
                'client_id' => $client->id,
                'client_secret' => $client->secret,
                'username' => $user->email,
                'password' => $password,
            ]
        );

        $response->assertOk();

        $response->assertHeader('pragma', 'no-cache');
        $response->assertHeader('cache-control', 'no-store, private');
        $response->assertHeader('content-type', 'application/json; charset=UTF-8');

        $decodedResponse = $response->decodeResponseJson()->json();

        $this->assertArrayHasKey('token_type', $decodedResponse);
        $this->assertArrayHasKey('expires_in', $decodedResponse);
        $this->assertArrayHasKey('access_token', $decodedResponse);
        $this->assertArrayHasKey('refresh_token', $decodedResponse);
        $this->assertSame('Bearer', $decodedResponse['token_type']);
        $expiresInSeconds = 31536000;
        $this->assertEqualsWithDelta($expiresInSeconds, $decodedResponse['expires_in'], 5);

        $jwtAccessToken = Configuration::forUnsecuredSigner()->parser()->parse($decodedResponse['access_token']);
        $this->assertTrue($this->app->make(ClientRepository::class)->findActive($jwtAccessToken->claims()->get('aud'))->is($client));
        $this->assertTrue($this->app->make('auth')->createUserProvider()->retrieveById($jwtAccessToken->claims()->get('sub'))->is($user));

        $token = $this->app->make(TokenRepository::class)->find($jwtAccessToken->claims()->get('jti'));
        $this->assertInstanceOf(Token::class, $token);
        $this->assertFalse($token->revoked);
        $this->assertTrue($token->user->is($user));
        $this->assertTrue($token->client->is($client));
        $this->assertNull($token->name);
        $this->assertLessThanOrEqual(5, CarbonImmutable::now()->addSeconds($expiresInSeconds)->diffInSeconds($token->expires_at));
    }

    public function testGettingAccessTokenWithPasswordGrantWithInvalidPassword()
    {
        $password = 'foobar123';
        $user = new User();
        $user->email = 'foo@gmail.com';
        $user->password = $this->app->make(Hasher::class)->make($password);
        $user->save();

        /** @var Client $client */
        $client = ClientFactory::new()->asPasswordClient()->create(['user_id' => $user->id]);

        $response = $this->post(
            '/oauth/token',
            [
                'grant_type' => 'password',
                'client_id' => $client->id,
                'client_secret' => $client->secret,
                'username' => $user->email,
                'password' => $password.'foo',
            ]
        );

        $response->assertStatus(400);

        $response->assertHeader('cache-control', 'no-cache, private');
        $response->assertHeader('content-type', 'application/json');

        $decodedResponse = $response->decodeResponseJson()->json();

        $this->assertArrayNotHasKey('token_type', $decodedResponse);
        $this->assertArrayNotHasKey('expires_in', $decodedResponse);
        $this->assertArrayNotHasKey('access_token', $decodedResponse);
        $this->assertArrayNotHasKey('refresh_token', $decodedResponse);

        $this->assertArrayHasKey('error', $decodedResponse);
        $this->assertSame('invalid_grant', $decodedResponse['error']);
        $this->assertArrayHasKey('error_description', $decodedResponse);
        $this->assertArrayHasKey('hint', $decodedResponse);
        $this->assertArrayHasKey('message', $decodedResponse);

        $this->assertSame(0, Token::count());
    }

    public function testGettingAccessTokenWithPasswordGrantWithInvalidClientSecret()
    {
        $password = 'foobar123';
        $user = new User();
        $user->email = 'foo@gmail.com';
        $user->password = $this->app->make(Hasher::class)->make($password);
        $user->save();

        /** @var Client $client */
        $client = ClientFactory::new()->asPasswordClient()->create(['user_id' => $user->id]);

        $response = $this->post(
            '/oauth/token',
            [
                'grant_type' => 'password',
                'client_id' => $client->id,
                'client_secret' => $client->secret.'foo',
                'username' => $user->email,
                'password' => $password,
            ]
        );

        $response->assertStatus(401);

        $response->assertHeader('cache-control', 'no-cache, private');
        $response->assertHeader('content-type', 'application/json');

        $decodedResponse = $response->decodeResponseJson()->json();

        $this->assertArrayNotHasKey('token_type', $decodedResponse);
        $this->assertArrayNotHasKey('expires_in', $decodedResponse);
        $this->assertArrayNotHasKey('access_token', $decodedResponse);
        $this->assertArrayNotHasKey('refresh_token', $decodedResponse);

        $this->assertArrayHasKey('error', $decodedResponse);
        $this->assertSame('invalid_client', $decodedResponse['error']);
        $this->assertArrayHasKey('error_description', $decodedResponse);
        $this->assertSame('Client authentication failed', $decodedResponse['error_description']);
        $this->assertArrayNotHasKey('hint', $decodedResponse);
        $this->assertArrayHasKey('message', $decodedResponse);
        $this->assertSame('Client authentication failed', $decodedResponse['message']);

        $this->assertSame(0, Token::count());
    }
}

class User extends \Illuminate\Foundation\Auth\User
{
    use HasApiTokens;
}
