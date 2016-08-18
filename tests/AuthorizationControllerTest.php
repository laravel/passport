<?php

use League\OAuth2\Server\AuthorizationServer;
use Illuminate\Contracts\Routing\ResponseFactory;

class AuthorizationControllerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function test_authorization_view_is_presented()
    {
        Laravel\Passport\Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = Mockery::mock(AuthorizationServer::class);
        $response = Mockery::mock(ResponseFactory::class);

        $controller = new Laravel\Passport\Http\Controllers\AuthorizationController($server, $response);

        $server->shouldReceive('validateAuthorizationRequest')->andReturn($authRequest = Mockery::mock());

        $request = Mockery::mock('Illuminate\Http\Request');
        $request->shouldReceive('session')->andReturn($session = Mockery::mock());
        $session->shouldReceive('put')->with('authRequest', $authRequest);
        $request->shouldReceive('user')->andReturn('user');

        $authRequest->shouldReceive('getClient->getIdentifier')->andReturn(1);
        $authRequest->shouldReceive('getScopes')->andReturn([new Laravel\Passport\Bridge\Scope('scope-1')]);

        $response->shouldReceive('view')->once()->andReturnUsing(function ($view, $data) use ($authRequest) {
            $this->assertEquals('passport::authorize', $view);
            $this->assertEquals('client', $data['client']);
            $this->assertEquals('user', $data['user']);
            $this->assertEquals('description', $data['scopes'][0]->description);

            return 'view';
        });

        $clients = Mockery::mock('Laravel\Passport\ClientRepository');
        $clients->shouldReceive('find')->with(1)->andReturn('client');

        $this->assertEquals('view', $controller->authorize(
            Mockery::mock('Psr\Http\Message\ServerRequestInterface'), $request, $clients
        ));
    }

    public function test_authorization_exceptions_are_handled()
    {
        $server = Mockery::mock(AuthorizationServer::class);
        $response = Mockery::mock(ResponseFactory::class);

        $controller = new Laravel\Passport\Http\Controllers\AuthorizationController($server, $response);

        $server->shouldReceive('validateAuthorizationRequest')->andReturnUsing(function () {
            throw new Exception('whoops');
        });

        $request = Mockery::mock('Illuminate\Http\Request');
        $request->shouldReceive('session')->andReturn($session = Mockery::mock());

        $clients = Mockery::mock('Laravel\Passport\ClientRepository');

        $this->assertEquals('whoops', $controller->authorize(
            Mockery::mock('Psr\Http\Message\ServerRequestInterface'), $request, $clients
        )->getOriginalContent());
    }
}
