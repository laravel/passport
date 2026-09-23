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
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Matching\Argument;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Guards\TokenGuard;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportUserProvider;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Workbench\App\Models\User as TokenGuardTestUser;

class TokenGuardTest extends TestCase
{
    use VerifiesDoubles;

    protected function tearDown(): void
    {
        Container::getInstance()->flush();
    }

    public function test_user_can_be_pulled_via_bearer_token()
    {
        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = Double::for(Encrypter::class);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $resourceServer->allows('validateAuthenticatedRequest')->returns($psr = Double::for(ServerRequestInterface::class));
        $psr->allows('getAttribute')->with('oauth_user_id')->returns(1);
        $psr->allows('getAttribute')->with('oauth_client_id')->returns(1);
        $psr->allows('getAttribute')->with('oauth_access_token_id')->returns('token');
        $psr->allows('getAttributes')->returns([
            'oauth_user_id' => 1,
            'oauth_client_id' => 1,
            'oauth_access_token_id' => 'token',
            'oauth_scopes' => [],
        ]);
        $userProvider->allows('retrieveById')->with(1)->returns(new TokenGuardTestUser);
        $userProvider->allows('getProviderName')->returns(null);
        $clients->allows('findActive')->with(1)->returns(new TokenGuardTestClient);

        $user = $guard->user();

        $this->assertInstanceOf(TokenGuardTestUser::class, $user);
        $this->assertEquals(AccessToken::fromPsrRequest($psr), $user->currentAccessToken());
    }

    public function test_user_is_resolved_only_once()
    {
        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = Double::for(Encrypter::class);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $resourceServer->allows('validateAuthenticatedRequest')->returns($psr = Double::for(ServerRequestInterface::class));
        $psr->allows('getAttribute')->with('oauth_user_id')->returns(1);
        $psr->allows('getAttribute')->with('oauth_client_id')->returns(1);
        $psr->allows('getAttribute')->with('oauth_access_token_id')->returns('token');
        $psr->allows('getAttributes')->returns([
            'oauth_user_id' => 1,
            'oauth_client_id' => 1,
            'oauth_access_token_id' => 'token',
            'oauth_scopes' => [],
        ]);
        $userProvider->allows('retrieveById')->with(1)->returns(new TokenGuardTestUser);
        $userProvider->allows('getProviderName')->returns(null);
        $clients->allows('findActive')->with(1)->returns(new TokenGuardTestClient);

        $user = $guard->user();

        $userProvider->expects('retrieveById')->never();

        $user2 = $guard->user();

        $this->assertInstanceOf(TokenGuardTestUser::class, $user);
        $this->assertEquals(AccessToken::fromPsrRequest($psr), $user->currentAccessToken());
        $this->assertSame($user, $user2);
    }

    public function test_no_user_is_returned_when_oauth_throws_exception()
    {
        $container = new Container;
        Container::setInstance($container);
        $container->instance(ExceptionHandler::class, $handler = Double::for(\stdClass::class));
        $handler->expects('report')->with(Argument::type(OAuthServerException::class));

        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = Double::for(Encrypter::class);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $resourceServer->allows('validateAuthenticatedRequest')->throws(new OAuthServerException('message', 500, 'error type'));

        $this->assertNull($guard->user());

        // Assert that `validateAuthenticatedRequest` isn't called twice on failure.
        $this->assertNull($guard->user());
    }

    public function test_null_is_returned_if_no_user_is_found()
    {
        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = Double::for(Encrypter::class);

        $clients->allows('findActive')->with(1)->returns(new TokenGuardTestClient);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $resourceServer->allows('validateAuthenticatedRequest')->returns($psr = Double::for(ServerRequestInterface::class));
        $psr->allows('getAttribute')->with('oauth_user_id')->returns(1);
        $psr->allows('getAttribute')->with('oauth_client_id')->returns(1);
        $userProvider->allows('retrieveById')->with(1)->returns(null);
        $userProvider->allows('getProviderName')->returns(null);

        $this->assertNull($guard->user());
    }

    public function test_null_is_returned_for_client_credentials_token()
    {
        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = Double::for(Encrypter::class);

        $clientId = '019c9d23-9763-7303-9bdb-3a0a6bf0ef90';

        $client = new TokenGuardTestClient;
        $client->grant_types = ['client_credentials'];

        $clients->allows('findActive')->with($clientId)->returns($client);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $resourceServer->allows('validateAuthenticatedRequest')->returns($psr = Double::for(ServerRequestInterface::class));
        $psr->allows('getAttribute')->with('oauth_user_id')->returns($clientId);
        $psr->allows('getAttribute')->with('oauth_client_id')->returns($clientId);
        $userProvider->allows('getProviderName')->returns(null);
        $userProvider->expects('retrieveById')->never();

        $this->assertNull($guard->user());
    }

    public function test_user_is_resolved_when_user_id_matches_client_id()
    {
        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = Double::for(Encrypter::class);

        $clients->allows('findActive')->with(1)->returns(new TokenGuardTestClient);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $resourceServer->allows('validateAuthenticatedRequest')->returns($psr = Double::for(ServerRequestInterface::class));
        $psr->allows('getAttribute')->with('oauth_user_id')->returns(1);
        $psr->allows('getAttribute')->with('oauth_client_id')->returns(1);
        $psr->allows('getAttribute')->with('oauth_access_token_id')->returns('token');
        $psr->allows('getAttributes')->returns([
            'oauth_user_id' => 1,
            'oauth_client_id' => 1,
            'oauth_access_token_id' => 'token',
            'oauth_scopes' => [],
        ]);
        $userProvider->allows('getProviderName')->returns(null);
        $userProvider->allows('retrieveById')->with(1)->returns(new TokenGuardTestUser);

        $user = $guard->user();

        $this->assertInstanceOf(TokenGuardTestUser::class, $user);
        $this->assertEquals(AccessToken::fromPsrRequest($psr), $user->currentAccessToken());
    }

    public function test_users_may_be_retrieved_from_cookies_with_csrf_token_header()
    {
        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = new Encrypter($key = str_repeat('a', 32), 'aes-256-cbc');

        $clients->allows('findActive')->with(1)->returns(new TokenGuardTestClient);

        $request = Request::create('/');
        $request->headers->set('X-CSRF-TOKEN', 'token');
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(CookieValuePrefix::create('laravel_token', $encrypter->getKey()).JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'exp' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], $key, 'HS256'), false)
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $userProvider->allows('retrieveById')->with(1)->returns($expectedUser = new TokenGuardTestUser);
        $userProvider->allows('getProviderName')->returns(null);

        $user = $guard->user();

        $this->assertEquals($expectedUser, $user);
    }

    public function test_users_may_be_retrieved_from_cookies_with_xsrf_token_header()
    {
        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = new Encrypter($key = str_repeat('a', 32), 'aes-256-cbc');

        $clients->allows('findActive')->with(1)->returns(new TokenGuardTestClient);

        $request = Request::create('/');
        $request->headers->set('X-XSRF-TOKEN', $encrypter->encrypt(CookieValuePrefix::create('X-XSRF-TOKEN', $encrypter->getKey()).'token', false));
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(CookieValuePrefix::create('laravel_token', $encrypter->getKey()).JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'exp' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], $key, 'HS256'), false)
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $userProvider->allows('retrieveById')->with(1)->returns($expectedUser = new TokenGuardTestUser);
        $userProvider->allows('getProviderName')->returns(null);

        $user = $guard->user();

        $this->assertEquals($expectedUser, $user);
    }

    public function test_cookie_xsrf_is_verified_against_csrf_token_header()
    {
        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = new Encrypter($key = str_repeat('a', 32), 'aes-256-cbc');

        $request = Request::create('/');
        $request->headers->set('X-CSRF-TOKEN', 'wrong_token');
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'exp' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], $key, 'HS256'))
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $userProvider->expects('retrieveById')->never();

        $this->assertNull($guard->user());
    }

    public function test_cookie_xsrf_is_verified_against_xsrf_token_header()
    {
        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = new Encrypter($key = str_repeat('a', 32), 'aes-256-cbc');

        $request = Request::create('/');
        $request->headers->set('X-XSRF-TOKEN', $encrypter->encrypt('wrong_token', false));
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'exp' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], $key, 'HS256'))
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $userProvider->expects('retrieveById')->never();

        $this->assertNull($guard->user());
    }

    public function test_users_may_be_retrieved_from_cookies_with_xsrf_token_header_when_using_a_custom_encryption_key()
    {
        Passport::encryptTokensUsing(function (EncrypterContract $encrypter) {
            return $encrypter->getKey().'.mykey';
        });

        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = new Encrypter($key = str_repeat('a', 32), 'aes-256-cbc');

        $clients->allows('findActive')->with(1)->returns(new TokenGuardTestClient);

        $request = Request::create('/');
        $request->headers->set('X-XSRF-TOKEN', $encrypter->encrypt(CookieValuePrefix::create('X-XSRF-TOKEN', $encrypter->getKey()).'token', false));
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(CookieValuePrefix::create('laravel_token', $encrypter->getKey()).JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'exp' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], Passport::tokenEncryptionKey($encrypter), 'HS256'), false)
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $userProvider->allows('retrieveById')->with(1)->returns($expectedUser = new TokenGuardTestUser);
        $userProvider->allows('getProviderName')->returns(null);

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

        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = new Encrypter($key = str_repeat('a', 32), 'aes-256-cbc');

        $clients->allows('findActive')->with(1)->returns(new TokenGuardTestClient);

        $request = Request::create('/');
        $request->headers->set('X-XSRF-TOKEN', $encrypter->encrypt(CookieValuePrefix::create('X-XSRF-TOKEN', $encrypter->getKey()).'token', false));
        $request->cookies->set('laravel_token',
            JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'exp' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], Passport::tokenEncryptionKey($encrypter), 'HS256')
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $userProvider->allows('retrieveById')->with(1)->returns($expectedUser = new TokenGuardTestUser);
        $userProvider->allows('getProviderName')->returns(null);

        $user = $guard->user();

        $this->assertEquals($expectedUser, $user);

        // Revert to the default encryption method
        Passport::withCookieEncryption();
        Passport::encryptTokensUsing(null);
    }

    public function test_xsrf_token_cookie_without_a_token_header_is_not_accepted()
    {
        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = new Encrypter($key = str_repeat('a', 32), 'aes-256-cbc');

        $request = Request::create('/');
        $request->cookies->set('XSRF-TOKEN', $encrypter->encrypt('token', false));
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'exp' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], $key, 'HS256'))
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $userProvider->expects('retrieveById')->never();

        $this->assertNull($guard->user());
    }

    public function test_expired_cookies_may_not_be_used()
    {
        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = new Encrypter($key = str_repeat('a', 32), 'aes-256-cbc');

        $request = Request::create('/');
        $request->headers->set('X-CSRF-TOKEN', 'token');
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'exp' => Carbon::now()->subMinutes(10)->getTimestamp(),
            ], $key, 'HS256'))
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $userProvider->expects('retrieveById')->never();

        $this->assertNull($guard->user());
    }

    public function test_csrf_check_can_be_disabled()
    {
        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = new Encrypter($key = str_repeat('a', 32), 'aes-256-cbc');

        $clients->allows('findActive')->with(1)->returns(new TokenGuardTestClient);

        Passport::ignoreCsrfToken();

        $request = Request::create('/');
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(CookieValuePrefix::create('laravel_token', $encrypter->getKey()).JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'exp' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], $key, 'HS256'), false)
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $userProvider->allows('retrieveById')->with(1)->returns($expectedUser = new TokenGuardTestUser);
        $userProvider->allows('getProviderName')->returns(null);

        $user = $guard->user();

        $this->assertEquals($expectedUser, $user);
    }

    public function test_client_can_be_pulled_via_bearer_token()
    {
        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = Double::for(Encrypter::class);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $resourceServer->allows('validateAuthenticatedRequest')->returns($psr = Double::for(ServerRequestInterface::class));
        $psr->allows('getAttribute')->with('oauth_client_id')->returns(1);
        $clients->allows('findActive')->with(1)->returns(new TokenGuardTestClient);

        $client = $guard->client();

        $this->assertInstanceOf(TokenGuardTestClient::class, $client);
    }

    public function test_client_is_resolved_only_once()
    {
        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = Double::for(Encrypter::class);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $resourceServer->allows('validateAuthenticatedRequest')->returns($psr = Double::for(ServerRequestInterface::class));
        $psr->allows('getAttribute')->with('oauth_client_id')->returns(1);
        $clients->allows('findActive')->with(1)->returns(new TokenGuardTestClient);

        $client = $guard->client();

        $clients->expects('findActive')->never();

        $client2 = $guard->client();

        $this->assertInstanceOf(TokenGuardTestClient::class, $client);
        $this->assertSame($client, $client2);
    }

    public function test_no_client_is_returned_when_oauth_throws_exception()
    {
        $container = new Container;
        Container::setInstance($container);
        $container->instance(ExceptionHandler::class, $handler = Double::for(\stdClass::class));
        $handler->expects('report')->with(Argument::type(OAuthServerException::class));

        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = Double::for(Encrypter::class);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $resourceServer->allows('validateAuthenticatedRequest')->throws(new OAuthServerException('message', 500, 'error type'));

        $this->assertNull($guard->client());

        // Assert that `validateAuthenticatedRequest` isn't called twice on failure.
        $this->assertNull($guard->client());
    }

    public function test_null_is_returned_if_no_client_is_found()
    {
        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = Double::for(Encrypter::class);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $resourceServer->allows('validateAuthenticatedRequest')->returns($psr = Double::for(ServerRequestInterface::class));
        $psr->allows('getAttribute')->with('oauth_client_id')->returns(1);
        $clients->allows('findActive')->with(1)->returns(null);

        $this->assertNull($guard->client());
    }

    public function test_clients_may_be_retrieved_from_cookies()
    {
        $resourceServer = Double::for(ResourceServer::class);
        $userProvider = Double::for(PassportUserProvider::class);
        $clients = Double::for(ClientRepository::class);
        $encrypter = new Encrypter($key = str_repeat('a', 32), 'aes-256-cbc');

        $request = Request::create('/');
        $request->headers->set('X-CSRF-TOKEN', 'token');
        $request->cookies->set('laravel_token',
            $encrypter->encrypt(CookieValuePrefix::create('laravel_token', $encrypter->getKey()).JWT::encode([
                'sub' => 1,
                'aud' => 1,
                'csrf' => 'token',
                'exp' => Carbon::now()->addMinutes(10)->getTimestamp(),
            ], $key, 'HS256'), false)
        );

        $guard = new TokenGuard($resourceServer, $userProvider, $clients, $encrypter, $request);

        $clients->allows('findActive')->with(1)->returns($expectedClient = new TokenGuardTestClient);

        $client = $guard->client();

        $this->assertEquals($expectedClient, $client);
    }
}

class TokenGuardTestClient extends Client
{
    public $provider;
}
