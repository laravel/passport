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

        $request->allows('session')->returns($session = Double::for(\stdClass::class));
        $request->allows('isNotFilled')->with('auth_token')->returns(false);
        $request->allows('input')->with('auth_token')->returns('foo');

        $authRequest = new AuthorizationRequest;
        $authRequest->setGrantTypeId('authorization_code');

        $session->expects('pull')->with('authToken')->returns('foo');
        $session->expects('pull')->with('authRequest')->returns(serialize($authRequest));

        $psrResponse = Double::for(ResponseInterface::class);
        app()->instance(ResponseInterface::class, (new PsrHttpFactory)->createResponse(new Response));

        $server->allows('completeAuthorizationRequest')->with(m::on(fn (AuthorizationRequest $request) => ! $request->isAuthorizationApproved()),
                m::type(ResponseInterface::class))->resolves(function () {
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

        $request->allows('session')->returns($session = Double::for(\stdClass::class));
        $request->expects('user')->never();
        $request->expects('input')->never();
        $request->allows('isNotFilled')->with('auth_token')->returns(false);
        $request->allows('input')->with('auth_token')->returns('foo');

        $session->expects('pull')->with('authToken')->returns('foo');
        $session->expects('pull')->with('authRequest')->returns(null);

        $psrResponse = Double::for(ResponseInterface::class);

        $server->expects('completeAuthorizationRequest')->never();

        $controller->deny($request, $psrResponse);
    }
}
