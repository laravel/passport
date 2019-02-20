<?php

namespace Laravel\Passport\Tests;

use Exception;
use Illuminate\Http\Request;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Mockery as m;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Laravel\Passport\Passport;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\Bridge\Scope;
use Illuminate\Container\Container;
use League\OAuth2\Server\AuthorizationServer;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Routing\ResponseFactory;
use Laravel\Passport\Http\Controllers\AuthorizationController;

class AuthorizationControllerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_authorization_view_is_presented()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(ResponseFactory::class);

        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('find')->with(1)->andReturn('client');

        $tokens = m::mock(TokenRepository::class);
        $tokens->shouldReceive('findValidToken')->with('user', 'client')->andReturnNull();

        $controller = new AuthorizationController($server, $response, $clients, $tokens);

        $server->shouldReceive('validateAuthorizationRequest')->andReturn($authRequest = m::mock());

        $request = m::mock(Request::class);
        $request->shouldReceive('session')->andReturn($session = m::mock());
        $session->shouldReceive('put')->with('authRequest', $authRequest);
        $request->shouldReceive('user')->andReturn('user');

        $authRequest->shouldReceive('getClient->getIdentifier')->andReturn(1);
        $authRequest->shouldReceive('getScopes')->andReturn([new Scope('scope-1')]);

        $response->shouldReceive('view')->once()->andReturnUsing(function ($view, $data) use ($authRequest) {
            $this->assertEquals('passport::authorize', $view);
            $this->assertEquals('client', $data['client']);
            $this->assertEquals('user', $data['user']);
            $this->assertEquals('description', $data['scopes'][0]->description);

            return 'view';
        });

        $this->assertEquals('view', $controller->authorize(
            m::mock(ServerRequestInterface::class), $request
        ));
    }

    public function test_authorization_exceptions_are_handled()
    {
        Container::getInstance()->instance(ExceptionHandler::class, $exceptions = m::mock());
        $exceptions->shouldReceive('report')->once();

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(ResponseFactory::class);
        $clients = m::mock(ClientRepository::class);
        $tokens = m::mock(TokenRepository::class);

        $controller = new AuthorizationController($server, $response, $clients, $tokens);

        $server->shouldReceive('validateAuthorizationRequest')->andThrow(new Exception('whoops'));

        $request = m::mock(Request::class);
        $request->shouldReceive('session')->andReturn($session = m::mock());

        $this->assertEquals('whoops', $controller->authorize(
            m::mock(ServerRequestInterface::class), $request
        )->getContent());
    }

    public function test_request_is_approved_if_valid_token_exists()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(ResponseFactory::class);

        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('find')->with(1)->andReturn('client');

        $user = m::mock();
        $user->shouldReceive('getKey')->andReturn(1);

        $tokens = m::mock(TokenRepository::class);
        $tokens->shouldReceive('findValidToken')
            ->with($user, 'client')
            ->andReturn($token = m::mock(Token::class));
        $token->shouldReceive('getAttribute')->with('scopes')->andReturn(['scope-1']);

        $controller = new AuthorizationController($server, $response, $clients, $tokens);
        $psrResponse = new Response();
        $psrResponse->getBody()->write('approved');
        $server->shouldReceive('validateAuthorizationRequest')
            ->andReturn($authRequest = m::mock(AuthorizationRequest::class));
        $server->shouldReceive('completeAuthorizationRequest')
            ->with($authRequest, m::type(ResponseInterface::class))
            ->andReturn($psrResponse);

        $request = m::mock(Request::class);
        $request->shouldReceive('user')->once()->andReturn($user);
        $request->shouldNotReceive('session');

        $authRequest->shouldReceive('getClient->getIdentifier')->once()->andReturn(1);
        $authRequest->shouldReceive('getScopes')->once()->andReturn([new Scope('scope-1')]);
        $authRequest->shouldReceive('setUser')->once()->andReturnNull();
        $authRequest->shouldReceive('setAuthorizationApproved')->once()->with(true);

        $this->assertEquals('approved', $controller->authorize(
            m::mock(ServerRequestInterface::class), $request
        )->getContent());
    }

    public function test_request_is_approved_if_overridden_method_allows()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(ResponseFactory::class);
        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('find')->with(1)->andReturn('client');
        $tokens = m::mock(TokenRepository::class);

        $controller = new class($server, $response, $clients, $tokens) extends AuthorizationController {
            protected function approveAutomatically($user, $client, $scopes) {
                return true;
            }
        };
        $psrResponse = new Response();
        $psrResponse->getBody()->write('approved');
        $server->shouldReceive('validateAuthorizationRequest')
            ->andReturn($authRequest = m::mock(AuthorizationRequest::class));
        $server->shouldReceive('completeAuthorizationRequest')
            ->with($authRequest, m::type(ResponseInterface::class))
            ->andReturn($psrResponse);

        $request = m::mock(Request::class);
        $request->shouldReceive('user')->once()->andReturn($user = m::mock());
        $user->shouldReceive('getKey')->andReturn(1);
        $request->shouldNotReceive('session');

        $authRequest->shouldReceive('getClient->getIdentifier')->once()->andReturn(1);
        $authRequest->shouldReceive('getScopes')->once()->andReturn([new Scope('scope-1')]);
        $authRequest->shouldReceive('setUser')->once()->andReturnNull();
        $authRequest->shouldReceive('setAuthorizationApproved')->once()->with(true);

        $this->assertEquals('approved', $controller->authorize(
            m::mock(ServerRequestInterface::class), $request
        )->getContent());
    }
    
    public function test_authorization_view_is_presented_if_overridden_method_denies()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(ResponseFactory::class);
        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('find')->with(1)->andReturn('client');
        $tokens = m::mock(TokenRepository::class);

        $controller = new class($server, $response, $clients, $tokens) extends AuthorizationController {
            protected function approveAutomatically($user, $client, $scopes) {
                return false;
            }
        };
        $server->shouldReceive('validateAuthorizationRequest')->andReturn($authRequest = m::mock());

        $request = m::mock(Request::class);
        $request->shouldReceive('session')->andReturn($session = m::mock());
        $session->shouldReceive('put')->with('authRequest', $authRequest);
        $request->shouldReceive('user')->andReturn('user');

        $authRequest->shouldReceive('getClient->getIdentifier')->andReturn(1);
        $authRequest->shouldReceive('getScopes')->once()->andReturn([new Scope('scope-1')]);
        $response->shouldReceive('view')->once()->andReturn('view');

        $this->assertEquals('view', $controller->authorize(
            m::mock(ServerRequestInterface::class), $request
        ));
    }
}
