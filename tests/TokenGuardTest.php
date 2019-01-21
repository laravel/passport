<?php

namespace Laravel\Passport\Tests;

use Mockery as m;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Laravel\Passport\Passport;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\HasApiTokens;
use Illuminate\Container\Container;
use Illuminate\Encryption\Encrypter;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Guards\TokenGuard;
use League\OAuth2\Server\ResourceServer;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use League\OAuth2\Server\Exception\OAuthServerException;

class TokenGuardTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_user_can_be_pulled_via_bearer_token()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(UserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = m::mock(Encrypter::class);

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_client_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn('token');
        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn(new TokenGuardTestUser);
        $tokens->shouldReceive('find')->once()->with('token')->andReturn($token = m::mock());
        $clients->shouldReceive('revoked')->with(1)->andReturn(false);

        $user = $guard->user($request);

        $this->assertInstanceOf(TokenGuardTestUser::class, $user);
        $this->assertEquals($token, $user->token());
    }

    public function test_no_user_is_returned_when_oauth_throws_exception()
    {
        $container = new Container;
        Container::setInstance($container);
        $container->instance(ExceptionHandler::class, $handler = m::mock());
        $handler->shouldReceive('report')->once()->with(m::type(OAuthServerException::class));

        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(UserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = m::mock(Encrypter::class);

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andThrow(
            new OAuthServerException('message', 500, 'error type')
        );

        $this->assertNull($guard->user($request));

        // Assert that `validateAuthenticatedRequest` isn't called twice on failure.
        $this->assertNull($guard->user($request));
    }

    public function test_null_is_returned_if_no_user_is_found()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(UserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = m::mock(Encrypter::class);

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);
        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn(null);

        $this->assertNull($guard->user($request));
    }

    public function test_users_may_be_retrieved_from_cookies()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(UserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = new Encrypter(str_repeat('a', 16));

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter);

        $request = Request::create('/');
        $request->headers->set('X-CSRF-TOKEN', 'token');
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'expiry' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], str_repeat('a', 16)), false)
        );

        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn($expectedUser = new TokenGuardTestUser);

        $user = $guard->user($request);

        $this->assertEquals($expectedUser, $user);
    }

    public function test_cookie_xsrf_is_verified_against_header()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(UserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = new Encrypter(str_repeat('a', 16));

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter);

        $request = Request::create('/');
        $request->headers->set('X-CSRF-TOKEN', 'wrong_token');
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'expiry' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], str_repeat('a', 16)))
        );

        $userProvider->shouldReceive('retrieveById')->never();

        $this->assertNull($guard->user($request));
    }

    public function test_expired_cookies_may_not_be_used()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(UserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = new Encrypter(str_repeat('a', 16));

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter);

        $request = Request::create('/');
        $request->headers->set('X-CSRF-TOKEN', 'token');
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'expiry' => Carbon::now()->subMinutes(10)->getTimestamp(),
            ], str_repeat('a', 16)))
        );

        $userProvider->shouldReceive('retrieveById')->never();

        $this->assertNull($guard->user($request));
    }

    public function test_csrf_check_can_be_disabled()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(UserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = new Encrypter(str_repeat('a', 16));

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter);

        Passport::ignoreCsrfToken();

        $request = Request::create('/');
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'expiry' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], str_repeat('a', 16)), false)
        );

        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn($expectedUser = new TokenGuardTestUser);

        $user = $guard->user($request);

        $this->assertEquals($expectedUser, $user);
    }

    public function test_client_can_be_pulled_via_bearer_token()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(UserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = m::mock(Encrypter::class);

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_client_id')->andReturn(1);
        $clients->shouldReceive('findActive')->with(1)->andReturn(new TokenGuardTestClient);

        $client = $guard->client($request);

        $this->assertInstanceOf(TokenGuardTestClient::class, $client);
    }

    public function test_no_client_is_returned_when_oauth_throws_exception()
    {
        $container = new Container;
        Container::setInstance($container);
        $container->instance(ExceptionHandler::class, $handler = m::mock());
        $handler->shouldReceive('report')->once()->with(m::type(OAuthServerException::class));

        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(UserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = m::mock(Encrypter::class);

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andThrow(
            new OAuthServerException('message', 500, 'error type')
        );

        $this->assertNull($guard->client($request));

        // Assert that `validateAuthenticatedRequest` isn't called twice on failure.
        $this->assertNull($guard->client($request));
    }

    public function test_null_is_returned_if_no_client_is_found()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(UserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = m::mock(Encrypter::class);

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_client_id')->andReturn(1);
        $clients->shouldReceive('findActive')->with(1)->andReturn(null);

        $this->assertNull($guard->client($request));
    }

    public function test_clients_may_be_retrieved_from_cookies()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(UserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = new Encrypter(str_repeat('a', 16));

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter);

        $request = Request::create('/');
        $request->headers->set('X-CSRF-TOKEN', 'token');
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'expiry' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], str_repeat('a', 16)), false)
        );

        $clients->shouldReceive('findActive')->with(1)->andReturn($expectedClient = new TokenGuardTestClient);

        $client = $guard->client($request);

        $this->assertEquals($expectedClient, $client);
    }
}

class TokenGuardTestUser
{
    use HasApiTokens;
}

class TokenGuardTestClient
{
}
