<?php

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Container\Container;
use Laravel\Passport\Guards\TokenGuard;

class TokenGuardTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function test_user_can_be_pulled_via_bearer_token()
    {
        $resourceServer = Mockery::mock('League\OAuth2\Server\ResourceServer');
        $userProvider = Mockery::mock('Illuminate\Contracts\Auth\UserProvider');
        $tokens = Mockery::mock('Laravel\Passport\TokenRepository');
        $clients = Mockery::mock('Laravel\Passport\ClientRepository');
        $encrypter = Mockery::mock('Illuminate\Contracts\Encryption\Encrypter');

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = Mockery::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_client_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn('token');
        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn(new TokenGuardTestUser);
        $tokens->shouldReceive('find')->once()->with('token')->andReturn($token = Mockery::mock());
        $clients->shouldReceive('revoked')->with(1)->andReturn(false);

        $user = $guard->user($request);

        $this->assertInstanceOf('TokenGuardTestUser', $user);
        $this->assertEquals($token, $user->token());
    }

    public function test_no_user_is_returned_when_oauth_throws_exception()
    {
        $container = new Container;
        Container::setInstance($container);
        $container->instance('Illuminate\Contracts\Debug\ExceptionHandler', $handler = Mockery::mock());
        $handler->shouldReceive('report')->once()->with(Mockery::type('League\OAuth2\Server\Exception\OAuthServerException'));

        $resourceServer = Mockery::mock('League\OAuth2\Server\ResourceServer');
        $userProvider = Mockery::mock('Illuminate\Contracts\Auth\UserProvider');
        $tokens = Mockery::mock('Laravel\Passport\TokenRepository');
        $clients = Mockery::mock('Laravel\Passport\ClientRepository');
        $encrypter = Mockery::mock('Illuminate\Contracts\Encryption\Encrypter');

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andThrow(
            new League\OAuth2\Server\Exception\OAuthServerException('message', 500, 'error type')
        );

        $this->assertNull($guard->user($request));
    }

    public function test_null_is_returned_if_no_user_is_found()
    {
        $resourceServer = Mockery::mock('League\OAuth2\Server\ResourceServer');
        $userProvider = Mockery::mock('Illuminate\Contracts\Auth\UserProvider');
        $tokens = Mockery::mock('Laravel\Passport\TokenRepository');
        $clients = Mockery::mock('Laravel\Passport\ClientRepository');
        $encrypter = Mockery::mock('Illuminate\Contracts\Encryption\Encrypter');

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = Mockery::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);
        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn(null);

        $this->assertNull($guard->user($request));
    }

    public function test_users_may_be_retrieved_from_cookies()
    {
        $resourceServer = Mockery::mock('League\OAuth2\Server\ResourceServer');
        $userProvider = Mockery::mock('Illuminate\Contracts\Auth\UserProvider');
        $tokens = Mockery::mock('Laravel\Passport\TokenRepository');
        $clients = Mockery::mock('Laravel\Passport\ClientRepository');
        $encrypter = new Illuminate\Encryption\Encrypter(str_repeat('a', 16));

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter);

        $request = Request::create('/');
        $request->headers->set('X-CSRF-TOKEN', 'token');
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(JWT::encode([
                'sub' => 1, 'csrf' => 'token',
                'expiry' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], str_repeat('a', 16)))
        );

        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn($expectedUser = new TokenGuardTestUser);

        $user = $guard->user($request);

        $this->assertEquals($expectedUser, $user);
    }

    public function test_cookie_xsrf_is_verified_against_header()
    {
        $resourceServer = Mockery::mock('League\OAuth2\Server\ResourceServer');
        $userProvider = Mockery::mock('Illuminate\Contracts\Auth\UserProvider');
        $tokens = Mockery::mock('Laravel\Passport\TokenRepository');
        $clients = Mockery::mock('Laravel\Passport\ClientRepository');
        $encrypter = new Illuminate\Encryption\Encrypter(str_repeat('a', 16));

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter);

        $request = Request::create('/');
        $request->headers->set('X-CSRF-TOKEN', 'wrong_token');
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(JWT::encode([
                'sub' => 1, 'csrf' => 'token',
                'expiry' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], str_repeat('a', 16)))
        );

        $userProvider->shouldReceive('retrieveById')->never();

        $this->assertNull($guard->user($request));
    }

    public function test_expired_cookies_may_not_be_used()
    {
        $resourceServer = Mockery::mock('League\OAuth2\Server\ResourceServer');
        $userProvider = Mockery::mock('Illuminate\Contracts\Auth\UserProvider');
        $tokens = Mockery::mock('Laravel\Passport\TokenRepository');
        $clients = Mockery::mock('Laravel\Passport\ClientRepository');
        $encrypter = new Illuminate\Encryption\Encrypter(str_repeat('a', 16));

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter);

        $request = Request::create('/');
        $request->headers->set('X-CSRF-TOKEN', 'token');
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(JWT::encode([
                'sub' => 1, 'csrf' => 'token',
                'expiry' => Carbon::now()->subMinutes(10)->getTimestamp(),
            ], str_repeat('a', 16)))
        );

        $userProvider->shouldReceive('retrieveById')->never();

        $this->assertNull($guard->user($request));
    }
}

class TokenGuardTestUser
{
    use Laravel\Passport\HasApiTokens;
}
