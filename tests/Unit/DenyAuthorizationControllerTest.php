<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\DenyAuthorizationController;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class DenyAuthorizationControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_authorization_can_be_denied()
    {
        $this->expectException('Laravel\Passport\Exceptions\OAuthServerException');

        $server = m::mock(AuthorizationServer::class);
        $controller = new DenyAuthorizationController($server);

        $request = m::mock(Request::class);

        $request->shouldReceive('session')->andReturn($session = m::mock());
        $request->shouldReceive('user')->andReturn(new DenyAuthorizationControllerFakeUser);
        $request->shouldReceive('has')->with('auth_token')->andReturn(true);
        $request->shouldReceive('get')->with('auth_token')->andReturn('foo');

        $session->shouldReceive('get')->once()->with('authToken')->andReturn('foo');
        $session->shouldReceive('get')
            ->once()
            ->with('authRequest')
            ->andReturn($authRequest = m::mock(
                AuthorizationRequest::class
            ));

        $authRequest->shouldReceive('setUser')->once();
        $authRequest->shouldReceive('setAuthorizationApproved')->once()->with(false);

        $server->shouldReceive('completeAuthorizationRequest')
            ->with($authRequest, m::type(ResponseInterface::class))
            ->andThrow('League\OAuth2\Server\Exception\OAuthServerException');

        $controller->deny($request);
    }

    public function test_auth_request_should_exist()
    {
        $this->expectException('Exception');
        $this->expectExceptionMessage('Authorization request was not present in the session.');

        $server = m::mock(AuthorizationServer::class);

        $controller = new DenyAuthorizationController($server);

        $request = m::mock(Request::class);

        $request->shouldReceive('session')->andReturn($session = m::mock());
        $request->shouldReceive('user')->never();
        $request->shouldReceive('input')->never();
        $request->shouldReceive('has')->with('auth_token')->andReturn(true);
        $request->shouldReceive('get')->with('auth_token')->andReturn('foo');

        $session->shouldReceive('get')->once()->with('authToken')->andReturn('foo');
        $session->shouldReceive('get')->once()->with('authRequest')->andReturnNull();

        $server->shouldReceive('completeAuthorizationRequest')->never();

        $controller->deny($request);
    }
}

class DenyAuthorizationControllerFakeUser
{
    public $id = 1;

    public function getAuthIdentifier()
    {
        return $this->id;
    }
}
