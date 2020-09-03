<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Http\Request;
use Laminas\Diactoros\Response;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Mockery as m;
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

        $session->shouldReceive('get')->once()->with('authToken')->andReturn('foo');
        $session->shouldReceive('get')
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
}

class ApproveAuthorizationControllerFakeUser
{
    public $id = 1;

    public function getAuthIdentifier()
    {
        return $this->id;
    }
}
