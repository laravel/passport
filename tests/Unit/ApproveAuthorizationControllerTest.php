<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ApproveAuthorizationControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_complete_authorization_request()
    {
        $server = m::mock(AuthorizationServer::class);

        $controller = new ApproveAuthorizationController($server);

        $request = m::mock(Request::class);
        $request->shouldReceive('session')->andReturn($session = m::mock());
        $request->shouldReceive('isNotFilled')->with('auth_token')->andReturn(false);
        $request->shouldReceive('get')->with('auth_token')->andReturn('foo');

        $session->shouldReceive('pull')->once()->with('authToken')->andReturn('foo');
        $session->shouldReceive('pull')
            ->once()
            ->with('authRequest')
            ->andReturn($authRequest = m::mock(AuthorizationRequest::class));

        $request->shouldReceive('user')->andReturn(new ApproveAuthorizationControllerFakeUser);

        $authRequest->shouldReceive('getGrantTypeId')->once()->andReturn('authorization_code');
        $authRequest->shouldReceive('setAuthorizationApproved')->once()->with(true);

        $psrResponse = new Response();
        $psrResponse->getBody()->write('response');

        $server->shouldReceive('completeAuthorizationRequest')
            ->with($authRequest, m::type(ResponseInterface::class))
            ->andReturn($psrResponse);

        $this->assertSame('response', $controller->approve($request, $psrResponse)->getContent());
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
