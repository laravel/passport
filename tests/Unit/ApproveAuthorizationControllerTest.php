<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Response;

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
        $request->shouldReceive('input')->with('auth_token')->andReturn('foo');

        $authRequest = new AuthorizationRequest;
        $authRequest->setGrantTypeId('authorization_code');

        $session->shouldReceive('pull')->once()->with('authToken')->andReturn('foo');
        $session->shouldReceive('pull')
            ->once()
            ->with('authRequest')
            ->andReturn(serialize($authRequest));

        $request->shouldReceive('user')->andReturn(new ApproveAuthorizationControllerFakeUser);

        $psrResponse = (new PsrHttpFactory)->createResponse(new Response);
        $psrResponse->getBody()->write('response');

        $server->shouldReceive('completeAuthorizationRequest')
            ->with(
                m::on(fn (AuthorizationRequest $request) => $request->isAuthorizationApproved()),
                m::type(ResponseInterface::class)
            )
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
