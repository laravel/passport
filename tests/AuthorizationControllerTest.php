<?php

use Illuminate\Container\Container;
use League\OAuth2\Server\AuthorizationServer;
use Illuminate\Contracts\Debug\ExceptionHandler;
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

        $tokens = Mockery::mock('Laravel\Passport\TokenRepository');
        $tokens->shouldReceive('findValidToken')->with('user', 'client')->andReturnNull();

        $this->assertEquals('view', $controller->authorize(
            Mockery::mock('Psr\Http\Message\ServerRequestInterface'), $request, $clients, $tokens
        ));
    }

    public function test_authorization_exceptions_are_handled()
    {
        Container::getInstance()->instance(ExceptionHandler::class, $exceptions = Mockery::mock());
        $exceptions->shouldReceive('report')->once();

        $server = Mockery::mock(AuthorizationServer::class);
        $response = Mockery::mock(ResponseFactory::class);

        $controller = new Laravel\Passport\Http\Controllers\AuthorizationController($server, $response);

        $server->shouldReceive('validateAuthorizationRequest')->andThrow(new Exception('whoops'));

        $request = Mockery::mock('Illuminate\Http\Request');
        $request->shouldReceive('session')->andReturn($session = Mockery::mock());

        $clients = Mockery::mock('Laravel\Passport\ClientRepository');

        $tokens = Mockery::mock('Laravel\Passport\TokenRepository');

        $this->assertEquals('whoops', $controller->authorize(
            Mockery::mock('Psr\Http\Message\ServerRequestInterface'), $request, $clients, $tokens
        )->getContent());
    }

    /**
     * @group shithead
     */
    public function test_request_is_approved_if_valid_token_exists()
    {
        Laravel\Passport\Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = Mockery::mock(AuthorizationServer::class);
        $response = Mockery::mock(ResponseFactory::class);

        $controller = new Laravel\Passport\Http\Controllers\AuthorizationController($server, $response);
        $psrResponse = new Zend\Diactoros\Response();
        $psrResponse->getBody()->write('approved');
        $server->shouldReceive('validateAuthorizationRequest')->andReturn($authRequest = Mockery::mock('League\OAuth2\Server\RequestTypes\AuthorizationRequest'));
        $server->shouldReceive('completeAuthorizationRequest')->with($authRequest, Mockery::type('Psr\Http\Message\ResponseInterface'))->andReturn($psrResponse);

        $request = Mockery::mock('Illuminate\Http\Request');
        $request->shouldReceive('user')->once()->andReturn($user = Mockery::mock());
        $user->shouldReceive('getKey')->andReturn(1);
        $request->shouldNotReceive('session');

        $authRequest->shouldReceive('getClient->getIdentifier')->once()->andReturn(1);
        $authRequest->shouldReceive('getScopes')->once()->andReturn([new Laravel\Passport\Bridge\Scope('scope-1')]);
        $authRequest->shouldReceive('setUser')->once()->andReturnNull();
        $authRequest->shouldReceive('setAuthorizationApproved')->once()->with(true);

        $clients = Mockery::mock('Laravel\Passport\ClientRepository');
        $clients->shouldReceive('find')->with(1)->andReturn('client');

        $tokens = Mockery::mock('Laravel\Passport\TokenRepository');
        $tokens->shouldReceive('findValidToken')->with($user, 'client')->andReturn($token = Mockery::mock('Laravel\Passport\Token'));
        $token->shouldReceive('getAttribute')->with('scopes')->andReturn(['scope-1']);

        $this->assertEquals('approved', $controller->authorize(
            Mockery::mock('Psr\Http\Message\ServerRequestInterface'), $request, $clients, $tokens
        )->getContent());
    }
}
