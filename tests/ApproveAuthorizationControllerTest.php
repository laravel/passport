<?php

namespace Laravel\Passport\Tests;

use Mockery as m;
use Illuminate\Http\Request;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;
use PHPUnit\Framework\TestCase;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;

class ApproveAuthorizationControllerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_complete_authorization_request()
    {
        $server = m::mock(AuthorizationServer::class);

        $controller = new ApproveAuthorizationController($server);

        $request = m::mock(Request::class);
        $request->shouldReceive('session')->andReturn($session = m::mock());
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

        $this->assertEquals('response', $controller->approve($request)->getContent());
    }
}

class ApproveAuthorizationControllerFakeUser
{
    public $id = 1;

    public function getKey()
    {
        return $this->id;
    }
}
