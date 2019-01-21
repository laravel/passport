<?php

namespace Laravel\Passport\Tests;

use Mockery as m;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Laravel\Passport\Http\Middleware\CheckClientCredentialsForAnyScope;

class CheckClientCredentialsForAnyScopeTest extends TestCase
{
    public function tearDown()
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

        $middleware = new CheckClientCredentialsForAnyScope($resourceServer);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $response = $middleware->handle($request, function () {
            return 'response';
        });

        $this->assertEquals('response', $response);
    }

    public function test_request_is_passed_along_if_token_has_any_required_scope()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_client_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn('token');
        $psr->shouldReceive('getAttribute')->with('oauth_scopes')->andReturn(['foo', 'bar', 'baz']);

        $middleware = new CheckClientCredentialsForAnyScope($resourceServer);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $response = $middleware->handle($request, function () {
            return 'response';
        }, 'notfoo', 'bar', 'notbaz');

        $this->assertEquals('response', $response);
    }

    /**
     * @expectedException \Illuminate\Auth\AuthenticationException
     */
    public function test_exception_is_thrown_when_oauth_throws_exception()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andThrow(
            new OAuthServerException('message', 500, 'error type')
        );

        $middleware = new CheckClientCredentialsForAnyScope($resourceServer);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $middleware->handle($request, function () {
            return 'response';
        });
    }

    /**
     * @expectedException \Laravel\Passport\Exceptions\MissingScopeException
     */
    public function test_exception_is_thrown_if_token_does_not_have_required_scope()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_client_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn('token');
        $psr->shouldReceive('getAttribute')->with('oauth_scopes')->andReturn(['foo', 'bar']);

        $middleware = new CheckClientCredentialsForAnyScope($resourceServer);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $response = $middleware->handle($request, function () {
            return 'response';
        }, 'baz', 'notbar');
    }
}
