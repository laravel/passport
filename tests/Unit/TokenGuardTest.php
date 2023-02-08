<?php

namespace Laravel\Passport\Tests\Unit;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Guards\TokenGuard;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportUserProvider;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class TokenGuardTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        Container::getInstance()->flush();
    }

    public function test_user_can_be_pulled_via_bearer_token()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(PassportUserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = m::mock(Encrypter::class);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter, $request);

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_client_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn('token');
        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn(new TokenGuardTestUser);
        $userProvider->shouldReceive('getProviderName')->andReturn(null);
        $tokens->shouldReceive('find')->once()->with('token')->andReturn($token = m::mock());
        $clients->shouldReceive('revoked')->with(1)->andReturn(false);
        $clients->shouldReceive('findActive')->with(1)->andReturn(new TokenGuardTestClient);

        $user = $guard->user();

        $this->assertInstanceOf(TokenGuardTestUser::class, $user);
        $this->assertEquals($token, $user->token());
    }

    public function test_user_is_resolved_only_once()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(PassportUserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = m::mock(Encrypter::class);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter, $request);

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_client_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn('token');
        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn(new TokenGuardTestUser);
        $userProvider->shouldReceive('getProviderName')->andReturn(null);
        $tokens->shouldReceive('find')->once()->with('token')->andReturn($token = m::mock());
        $clients->shouldReceive('revoked')->with(1)->andReturn(false);
        $clients->shouldReceive('findActive')->with(1)->andReturn(new TokenGuardTestClient);

        $user = $guard->user();

        $userProvider->shouldReceive('retrieveById')->never();

        $user2 = $guard->user();

        $this->assertInstanceOf(TokenGuardTestUser::class, $user);
        $this->assertEquals($token, $user->token());
        $this->assertSame($user, $user2);
    }

    public function test_no_user_is_returned_when_oauth_throws_exception()
    {
        $container = new Container;
        Container::setInstance($container);
        $container->instance(ExceptionHandler::class, $handler = m::mock());
        $handler->shouldReceive('report')->once()->with(m::type(OAuthServerException::class));

        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(PassportUserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = m::mock(Encrypter::class);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter, $request);

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andThrow(
            new OAuthServerException('message', 500, 'error type')
        );

        $this->assertNull($guard->user());

        // Assert that `validateAuthenticatedRequest` isn't called twice on failure.
        $this->assertNull($guard->user());
    }

    public function test_null_is_returned_if_no_user_is_found()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(PassportUserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = m::mock(Encrypter::class);

        $clients->shouldReceive('findActive')
            ->with(1)
            ->andReturn(new TokenGuardTestClient);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter, $request);

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_client_id')->andReturn(1);
        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn(null);
        $userProvider->shouldReceive('getProviderName')->andReturn(null);

        $this->assertNull($guard->user());
    }

    public function test_users_may_be_retrieved_from_cookies_with_csrf_token_header()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(PassportUserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = new Encrypter(str_repeat('a', 16));

        $clients->shouldReceive('findActive')
            ->with(1)
            ->andReturn(new TokenGuardTestClient);

        $request = Request::create('/');
        $request->headers->set('X-CSRF-TOKEN', 'token');
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(CookieValuePrefix::create('laravel_token', $encrypter->getKey()).JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'expiry' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], str_repeat('a', 16), 'HS256'), false)
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter, $request);

        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn($expectedUser = new TokenGuardTestUser);
        $userProvider->shouldReceive('getProviderName')->andReturn(null);

        $user = $guard->user();

        $this->assertEquals($expectedUser, $user);
    }

    public function test_users_may_be_retrieved_from_cookies_with_xsrf_token_header()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(PassportUserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = new Encrypter(str_repeat('a', 16));

        $clients->shouldReceive('findActive')
            ->with(1)
            ->andReturn(new TokenGuardTestClient);

        $request = Request::create('/');
        $request->headers->set('X-XSRF-TOKEN', $encrypter->encrypt(CookieValuePrefix::create('X-XSRF-TOKEN', $encrypter->getKey()).'token', false));
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(CookieValuePrefix::create('laravel_token', $encrypter->getKey()).JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'expiry' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], str_repeat('a', 16), 'HS256'), false)
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter, $request);

        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn($expectedUser = new TokenGuardTestUser);
        $userProvider->shouldReceive('getProviderName')->andReturn(null);

        $user = $guard->user();

        $this->assertEquals($expectedUser, $user);
    }

    public function test_cookie_xsrf_is_verified_against_csrf_token_header()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(PassportUserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = new Encrypter(str_repeat('a', 16));

        $request = Request::create('/');
        $request->headers->set('X-CSRF-TOKEN', 'wrong_token');
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'expiry' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], str_repeat('a', 16), 'HS256'))
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter, $request);

        $userProvider->shouldReceive('retrieveById')->never();

        $this->assertNull($guard->user());
    }

    public function test_cookie_xsrf_is_verified_against_xsrf_token_header()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(PassportUserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = new Encrypter(str_repeat('a', 16));

        $request = Request::create('/');
        $request->headers->set('X-XSRF-TOKEN', $encrypter->encrypt('wrong_token', false));
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'expiry' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], str_repeat('a', 16), 'HS256'))
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter, $request);

        $userProvider->shouldReceive('retrieveById')->never();

        $this->assertNull($guard->user());
    }

    public function test_users_may_be_retrieved_from_cookies_with_xsrf_token_header_when_using_a_custom_encryption_key()
    {
        Passport::encryptTokensUsing(function (EncrypterContract $encrypter) {
            return $encrypter->getKey().'.mykey';
        });

        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(PassportUserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = new Encrypter(str_repeat('a', 16));

        $clients->shouldReceive('findActive')
            ->with(1)
            ->andReturn(new TokenGuardTestClient);

        $request = Request::create('/');
        $request->headers->set('X-XSRF-TOKEN', $encrypter->encrypt(CookieValuePrefix::create('X-XSRF-TOKEN', $encrypter->getKey()).'token', false));
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(CookieValuePrefix::create('laravel_token', $encrypter->getKey()).JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'expiry' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], Passport::tokenEncryptionKey($encrypter), 'HS256'), false)
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter, $request);

        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn($expectedUser = new TokenGuardTestUser);
        $userProvider->shouldReceive('getProviderName')->andReturn(null);

        $user = $guard->user();

        $this->assertEquals($expectedUser, $user);

        // Revert to the default encryption method
        Passport::encryptTokensUsing(null);
    }

    public function test_users_may_be_retrieved_from_cookies_without_encryption()
    {
        Passport::withoutCookieEncryption();
        Passport::encryptTokensUsing(function (EncrypterContract $encrypter) {
            return $encrypter->getKey().'.mykey';
        });

        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(PassportUserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = new Encrypter(str_repeat('a', 16));

        $clients->shouldReceive('findActive')
            ->with(1)
            ->andReturn(new TokenGuardTestClient);

        $request = Request::create('/');
        $request->headers->set('X-XSRF-TOKEN', $encrypter->encrypt(CookieValuePrefix::create('X-XSRF-TOKEN', $encrypter->getKey()).'token', false));
        $request->cookies->set('laravel_token',
            JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'expiry' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], Passport::tokenEncryptionKey($encrypter), 'HS256')
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter, $request);

        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn($expectedUser = new TokenGuardTestUser);
        $userProvider->shouldReceive('getProviderName')->andReturn(null);

        $user = $guard->user();

        $this->assertEquals($expectedUser, $user);

        // Revert to the default encryption method
        Passport::withCookieEncryption();
        Passport::encryptTokensUsing(null);
    }

    public function test_xsrf_token_cookie_without_a_token_header_is_not_accepted()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(PassportUserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = new Encrypter(str_repeat('a', 16));

        $request = Request::create('/');
        $request->cookies->set('XSRF-TOKEN', $encrypter->encrypt('token', false));
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'expiry' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], str_repeat('a', 16), 'HS256'))
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter, $request);

        $userProvider->shouldReceive('retrieveById')->never();

        $this->assertNull($guard->user());
    }

    public function test_expired_cookies_may_not_be_used()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(PassportUserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = new Encrypter(str_repeat('a', 16));

        $request = Request::create('/');
        $request->headers->set('X-CSRF-TOKEN', 'token');
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'expiry' => Carbon::now()->subMinutes(10)->getTimestamp(),
            ], str_repeat('a', 16), 'HS256'))
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter, $request);

        $userProvider->shouldReceive('retrieveById')->never();

        $this->assertNull($guard->user());
    }

    public function test_csrf_check_can_be_disabled()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(PassportUserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = new Encrypter(str_repeat('a', 16));

        $clients->shouldReceive('findActive')
            ->with(1)
            ->andReturn(new TokenGuardTestClient);

        Passport::ignoreCsrfToken();

        $request = Request::create('/');
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(CookieValuePrefix::create('laravel_token', $encrypter->getKey()).JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'expiry' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], str_repeat('a', 16), 'HS256'), false)
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter, $request);

        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn($expectedUser = new TokenGuardTestUser);
        $userProvider->shouldReceive('getProviderName')->andReturn(null);

        $user = $guard->user();

        $this->assertEquals($expectedUser, $user);
    }

    public function test_client_can_be_pulled_via_bearer_token()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(PassportUserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = m::mock(Encrypter::class);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter, $request);

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_client_id')->andReturn(1);
        $clients->shouldReceive('findActive')->with(1)->andReturn(new TokenGuardTestClient);

        $client = $guard->client();

        $this->assertInstanceOf(TokenGuardTestClient::class, $client);
    }

    public function test_client_is_resolved_only_once()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(PassportUserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = m::mock(Encrypter::class);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter, $request);

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_client_id')->andReturn(1);
        $clients->shouldReceive('findActive')->with(1)->andReturn(new TokenGuardTestClient);

        $client = $guard->client();

        $clients->shouldReceive('findActive')->never();

        $client2 = $guard->client();

        $this->assertInstanceOf(TokenGuardTestClient::class, $client);
        $this->assertSame($client, $client2);
    }

    public function test_no_client_is_returned_when_oauth_throws_exception()
    {
        $container = new Container;
        Container::setInstance($container);
        $container->instance(ExceptionHandler::class, $handler = m::mock());
        $handler->shouldReceive('report')->once()->with(m::type(OAuthServerException::class));

        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(PassportUserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = m::mock(Encrypter::class);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter, $request);

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andThrow(
            new OAuthServerException('message', 500, 'error type')
        );

        $this->assertNull($guard->client());

        // Assert that `validateAuthenticatedRequest` isn't called twice on failure.
        $this->assertNull($guard->client());
    }

    public function test_null_is_returned_if_no_client_is_found()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(PassportUserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = m::mock(Encrypter::class);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter, $request);

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_client_id')->andReturn(1);
        $clients->shouldReceive('findActive')->with(1)->andReturn(null);

        $this->assertNull($guard->client());
    }

    public function test_clients_may_be_retrieved_from_cookies()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $userProvider = m::mock(PassportUserProvider::class);
        $tokens = m::mock(TokenRepository::class);
        $clients = m::mock(ClientRepository::class);
        $encrypter = new Encrypter(str_repeat('a', 16));

        $request = Request::create('/');
        $request->headers->set('X-CSRF-TOKEN', 'token');
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(CookieValuePrefix::create('laravel_token', $encrypter->getKey()).JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'expiry' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], str_repeat('a', 16), 'HS256'), false)
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $tokens, $clients, $encrypter, $request);

        $clients->shouldReceive('findActive')->with(1)->andReturn($expectedClient = new TokenGuardTestClient);

        $client = $guard->client();

        $this->assertEquals($expectedClient, $client);
    }
}

class TokenGuardTestUser
{
    use HasApiTokens;
}

class TokenGuardTestClient
{
    public $provider;
}
