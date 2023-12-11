<?php

namespace Laravel\Passport\Tests;

use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Laravel\Passport\Bridge\Scope;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Exceptions\OAuthServerException;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Laravel\Passport\Http\Controllers\DeviceAuthorizationController;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException as LeagueException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use League\OAuth2\Server\RequestTypes\DeviceAuthorizationRequest;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DeviceAuthorizationControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_complete_device_authorization_request()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = m::mock(AuthorizationServer::class);

        $controller = new DeviceAuthorizationController($server);

        $server->shouldReceive('validateDeviceAuthorizationRequest')->andReturn($deviceAuthRequest = m::mock(DeviceAuthorizationRequest::class));

        $psrResponse = new \Zend\Diactoros\Response();
        $psrResponse->getBody()->write('response');

        $server->shouldReceive('completeDeviceAuthorizationRequest')
            ->with($deviceAuthRequest, m::type(ResponseInterface::class))
            ->andReturn($psrResponse);

        $this->assertEquals('response', $controller->authorize(m::mock(ServerRequestInterface::class))->getContent());

    }

    public function test_authorization_exceptions_are_handled()
    {
        $server = m::mock(AuthorizationServer::class);

        $controller = new DeviceAuthorizationController($server);

        $server->shouldReceive('validateDeviceAuthorizationRequest')->andThrow(LeagueException::invalidCredentials());

        $this->expectException(OAuthServerException::class);

        $controller->authorize(m::mock(ServerRequestInterface::class));
    }
}
