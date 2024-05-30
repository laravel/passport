<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Mockery as m;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ApproveAuthorizationControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_complete_authorization_request()
    {
        $server = m::mock(AuthorizationServer::class);

        $controller = new ApproveAuthorizationController($server);

        $request = m::mock(Request::class);
        $request->shouldReceive('session')->andReturn($session = m::mock());
        $request->shouldReceive('has')->with('auth_token')->andReturn(true);
        $request->shouldReceive('get')->with('auth_token')->andReturn('foo');

        $session->shouldReceive('pull')->once()->with('authToken')->andReturn('foo');
        $session->shouldReceive('pull')
            ->once()
            ->with('authRequest')
            ->andReturn($authRequest = m::mock(AuthorizationRequest::class));

        $request->shouldReceive('user')->andReturn(new ApproveAuthorizationControllerFakeUser);

        $authRequest->shouldReceive('getClient->getIdentifier')->andReturn(1);
        $authRequest->shouldReceive('getUser->getIdentifier')->andReturn(2);
        $authRequest->shouldReceive('setUser')->once();
        $authRequest->shouldReceive('setAuthorizationApproved')->once()->with(true);

        $psrResponse = new Response();
        $psrResponse->getBody()->write('response');

        $server->shouldReceive('completeAuthorizationRequest')
            ->with($authRequest, m::type(ResponseInterface::class))
            ->andReturn($psrResponse);

        $this->assertSame('response', $controller->approve($request)->getContent());
    }

    public function test_auth_request_should_exist()
    {
        $this->expectException('Exception');
        $this->expectExceptionMessage('Authorization request was not present in the session.');

        $server = m::mock(AuthorizationServer::class);

        $controller = new ApproveAuthorizationController($server);

        $request = m::mock(Request::class);

        $request->shouldReceive('session')->andReturn($session = m::mock());
        $request->shouldReceive('user')->never();
        $request->shouldReceive('input')->never();
        $request->shouldReceive('has')->with('auth_token')->andReturn(true);
        $request->shouldReceive('get')->with('auth_token')->andReturn('foo');

        $session->shouldReceive('pull')->once()->with('authToken')->andReturn('foo');
        $session->shouldReceive('pull')->once()->with('authRequest')->andReturnNull();

        $server->shouldReceive('completeAuthorizationRequest')->never();

        $controller->approve($request);
    }

    public function test_auth_token_should_exist_on_request()
    {
        $this->expectException('\Laravel\Passport\Exceptions\InvalidAuthTokenException');
        $this->expectExceptionMessage('The provided auth token for the request is different from the session auth token.');

        $server = m::mock(AuthorizationServer::class);

        $controller = new ApproveAuthorizationController($server);

        $request = m::mock(Request::class);

        $request->shouldReceive('session')->andReturn($session = m::mock());
        $request->shouldReceive('user')->never();
        $request->shouldReceive('input')->never();
        $request->shouldReceive('has')->with('auth_token')->andReturn(false);

        $session->shouldReceive('forget')->once()->with(['authToken', 'authRequest']);

        $server->shouldReceive('completeAuthorizationRequest')->never();

        $controller->approve($request);
    }
}

class ApproveAuthorizationControllerFakeUser
{
    public $id = 1;

    public function getAuthIdentifier()
    {
        return $this->id;
    }
}
