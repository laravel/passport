<?php

namespace Laravel\Passport\Tests\Unit;

use JMac\Testing\Double;
use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\DenyAuthorizationController;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Response;

class DenyAuthorizationControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_authorization_can_be_denied()
    {
        $this->expectException('Laravel\Passport\Exceptions\OAuthServerException');

        $server = Double::for(AuthorizationServer::class);
        $controller = new DenyAuthorizationController($server);

        $request = Double::for(Request::class);

        $request->shouldReceive('session')->andReturn($session = Double::for(\stdClass::class));
        $request->shouldReceive('isNotFilled')->with('auth_token')->andReturn(false);
        $request->shouldReceive('input')->with('auth_token')->andReturn('foo');

        $authRequest = new AuthorizationRequest;
        $authRequest->setGrantTypeId('authorization_code');

        $session->shouldReceive('pull')->once()->with('authToken')->andReturn('foo');
        $session->shouldReceive('pull')
            ->once()
            ->with('authRequest')
            ->andReturn(serialize($authRequest));

        $psrResponse = Double::for(ResponseInterface::class);
        app()->instance(ResponseInterface::class, (new PsrHttpFactory)->createResponse(new Response));

        $server->shouldReceive('completeAuthorizationRequest')
            ->with(
                m::on(fn (AuthorizationRequest $request) => ! $request->isAuthorizationApproved()),
                m::type(ResponseInterface::class)
            )
            ->andReturnUsing(function () {
                throw new \League\OAuth2\Server\Exception\OAuthServerException('', 0, '');
            });

        $controller->deny($request, $psrResponse);
    }

    public function test_auth_request_should_exist()
    {
        $this->expectException('Exception');
        $this->expectExceptionMessage('Authorization request was not present in the session.');

        $server = Double::for(AuthorizationServer::class);

        $controller = new DenyAuthorizationController($server);

        $request = Double::for(Request::class);

        $request->shouldReceive('session')->andReturn($session = Double::for(\stdClass::class));
        $request->shouldReceive('user')->never();
        $request->shouldReceive('input')->never();
        $request->shouldReceive('isNotFilled')->with('auth_token')->andReturn(false);
        $request->shouldReceive('input')->with('auth_token')->andReturn('foo');

        $session->shouldReceive('pull')->once()->with('authToken')->andReturn('foo');
        $session->shouldReceive('pull')->once()->with('authRequest')->andReturnNull();

        $psrResponse = Double::for(ResponseInterface::class);

        $server->shouldReceive('completeAuthorizationRequest')->never();

        $controller->deny($request, $psrResponse);
    }
}
