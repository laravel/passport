<?php

namespace Laravel\Passport\Tests;

use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Laravel\Passport\Http\Middleware\CheckClientCredentials;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class CheckClientCredentialsTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_request_is_passed_along_if_token_is_valid()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_client_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn('token');
        $psr->shouldReceive('getAttribute')->with('oauth_scopes')->andReturn(['*']);

        $client = m::mock(Client::class);
        $client->shouldReceive('firstParty')->andReturnFalse();

        $token = m::mock(Token::class);
        $token->shouldReceive('getAttribute')->with('client')->andReturn($client);
        $token->shouldReceive('getAttribute')->with('scopes')->andReturn(['*']);

        $tokenRepository = m::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('find')->with('token')->andReturn($token);

        $middleware = new CheckClientCredentials($resourceServer, $tokenRepository);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $response = $middleware->handle($request, function () {
            return 'response';
        });

        $this->assertEquals('response', $response);
    }

    public function test_request_is_passed_along_if_token_and_scope_are_valid()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_client_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn('token');
        $psr->shouldReceive('getAttribute')->with('oauth_scopes')->andReturn(['see-profile']);

        $client = m::mock(Client::class);
        $client->shouldReceive('firstParty')->andReturnFalse();

        $token = m::mock(Token::class);
        $token->shouldReceive('getAttribute')->with('client')->andReturn($client);
        $token->shouldReceive('getAttribute')->with('scopes')->andReturn(['see-profile']);
        $token->shouldReceive('cant')->with('see-profile')->andReturnFalse();

        $tokenRepository = m::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('find')->with('token')->andReturn($token);

        $middleware = new CheckClientCredentials($resourceServer, $tokenRepository);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $response = $middleware->handle($request, function () {
            return 'response';
        }, 'see-profile');

        $this->assertEquals('response', $response);
    }

    public function test_exception_is_thrown_when_oauth_throws_exception()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');

        $tokenRepository = m::mock(TokenRepository::class);
        $resourceServer = m::mock(ResourceServer::class);
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andThrow(
            new OAuthServerException('message', 500, 'error type')
        );

        $middleware = new CheckClientCredentials($resourceServer, $tokenRepository);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $middleware->handle($request, function () {
            return 'response';
        });
    }

    public function test_exception_is_thrown_if_token_does_not_have_required_scopes()
    {
        $this->expectException('Laravel\Passport\Exceptions\MissingScopeException');

        $resourceServer = m::mock(ResourceServer::class);
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_client_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn('token');
        $psr->shouldReceive('getAttribute')->with('oauth_scopes')->andReturn(['foo', 'notbar']);

        $client = m::mock(Client::class);
        $client->shouldReceive('firstParty')->andReturnFalse();

        $token = m::mock(Token::class);
        $token->shouldReceive('getAttribute')->with('client')->andReturn($client);
        $token->shouldReceive('getAttribute')->with('scopes')->andReturn(['foo', 'notbar']);
        $token->shouldReceive('cant')->with('foo')->andReturnFalse();
        $token->shouldReceive('cant')->with('bar')->andReturnTrue();

        $tokenRepository = m::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('find')->with('token')->andReturn($token);

        $middleware = new CheckClientCredentials($resourceServer, $tokenRepository);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $response = $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }

    public function test_exception_is_thrown_if_token_belongs_to_first_party_client()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');

        $resourceServer = m::mock(ResourceServer::class);
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_client_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn('token');
        $psr->shouldReceive('getAttribute')->with('oauth_scopes')->andReturn(['*']);

        $client = m::mock(Client::class);
        $client->shouldReceive('firstParty')->andReturnTrue();

        $token = m::mock(Token::class);
        $token->shouldReceive('getAttribute')->with('client')->andReturn($client);

        $tokenRepository = m::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('find')->with('token')->andReturn($token);

        $middleware = new CheckClientCredentials($resourceServer, $tokenRepository);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $response = $middleware->handle($request, function () {
            return 'response';
        });
    }
}
