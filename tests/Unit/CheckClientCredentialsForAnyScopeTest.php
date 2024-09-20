<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Http\Request;
use Laravel\Passport\Exceptions\AuthenticationException;
use Laravel\Passport\Http\Middleware\CheckClientCredentialsForAnyScope;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class CheckClientCredentialsForAnyScopeTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_request_is_passed_along_if_token_is_valid()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock(ServerRequestInterface::class));
        $psr->shouldReceive('getAttributes')->andReturn([
            'oauth_user_id' => 1,
            'oauth_client_id' => 1,
            'oauth_access_token_id' => 'token',
            'oauth_scopes' => ['*'],
        ]);

        $middleware = new CheckClientCredentialsForAnyScope($resourceServer);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $response = $middleware->handle($request, function () {
            return 'response';
        });

        $this->assertSame('response', $response);
    }

    public function test_request_is_passed_along_if_token_has_any_required_scope()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock(ServerRequestInterface::class));
        $psr->shouldReceive('getAttributes')->andReturn([
            'oauth_user_id' => 1,
            'oauth_client_id' => 1,
            'oauth_access_token_id' => 'token',
            'oauth_scopes' => ['foo', 'bar', 'baz'],
        ]);

        $middleware = new CheckClientCredentialsForAnyScope($resourceServer);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $response = $middleware->handle($request, function () {
            return 'response';
        }, 'notfoo', 'bar', 'notbaz');

        $this->assertSame('response', $response);
    }

    public function test_exception_is_thrown_when_oauth_throws_exception()
    {
        $this->expectException(AuthenticationException::class);

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

    public function test_exception_is_thrown_if_token_does_not_have_required_scope()
    {
        $this->expectException('Laravel\Passport\Exceptions\MissingScopeException');

        $resourceServer = m::mock(ResourceServer::class);
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock(ServerRequestInterface::class));
        $psr->shouldReceive('getAttributes')->andReturn([
            'oauth_user_id' => 1,
            'oauth_client_id' => 1,
            'oauth_access_token_id' => 'token',
            'oauth_scopes' => ['foo', 'bar'],
        ]);

        $middleware = new CheckClientCredentialsForAnyScope($resourceServer);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $response = $middleware->handle($request, function () {
            return 'response';
        }, 'baz', 'notbar');
    }
}
