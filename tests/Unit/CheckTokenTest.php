<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Exceptions\AuthenticationException;
use Laravel\Passport\Http\Middleware\CheckToken;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class CheckTokenTest extends TestCase
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

        $middleware = new CheckToken($resourceServer);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $response = $middleware->handle($request, function () {
            return new Response('response');
        });

        $this->assertSame('response', $response->getContent());
    }

    public function test_request_is_passed_along_if_token_and_scope_are_valid()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock(ServerRequestInterface::class));
        $psr->shouldReceive('getAttributes')->andReturn([
            'oauth_user_id' => 1,
            'oauth_client_id' => 1,
            'oauth_access_token_id' => 'token',
            'oauth_scopes' => ['see-profile'],
        ]);

        $middleware = new CheckToken($resourceServer);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $response = $middleware->handle($request, function () {
            return new Response('response');
        }, 'see-profile');

        $this->assertSame('response', $response->getContent());
    }

    public function test_exception_is_thrown_when_oauth_throws_exception()
    {
        $this->expectException(AuthenticationException::class);

        $resourceServer = m::mock(ResourceServer::class);
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andThrow(
            new OAuthServerException('message', 500, 'error type')
        );

        $middleware = new CheckToken($resourceServer);

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
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock(ServerRequestInterface::class));
        $psr->shouldReceive('getAttributes')->andReturn([
            'oauth_user_id' => 1,
            'oauth_client_id' => 1,
            'oauth_access_token_id' => 'token',
            'oauth_scopes' => ['foo', 'notbar'],
        ]);

        $middleware = new CheckToken($resourceServer);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $response = $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }

    public function test_request_is_passed_along_if_scopes_are_present_on_token()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $middleware = new CheckToken($resourceServer);
        $request = m::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user = m::mock());
        $user->shouldReceive('token')->andReturn($token = m::mock(AccessToken::class));
        $token->shouldReceive('cant')->with('foo')->andReturn(false);
        $token->shouldReceive('cant')->with('bar')->andReturn(false);

        $response = $middleware->handle($request, function () {
            return new Response('response');
        }, 'foo', 'bar');

        $this->assertSame('response', $response->getContent());
    }

    public function test_exception_is_thrown_if_token_doesnt_have_scope()
    {
        $this->expectException('Laravel\Passport\Exceptions\MissingScopeException');

        $resourceServer = m::mock(ResourceServer::class);
        $middleware = new CheckToken($resourceServer);
        $request = m::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user = m::mock());
        $user->shouldReceive('token')->andReturn($token = m::mock(AccessToken::class));
        $token->shouldReceive('cant')->with('foo')->andReturn(true);

        $middleware->handle($request, function () {
            return new Response('response');
        }, 'foo', 'bar');
    }
}
