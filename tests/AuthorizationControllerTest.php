<?php

namespace Laravel\Passport\Tests;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Laravel\Passport\Bridge\Scope;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;

class AuthorizationControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        Container::getInstance()->flush();
    }

    public function test_authorization_view_is_presented()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(ResponseFactory::class);

        $controller = new AuthorizationController($server, $response);

        $server->shouldReceive('validateAuthorizationRequest')->andReturn($authRequest = m::mock());

        $request = m::mock(Request::class);
        $request->shouldReceive('session')->andReturn($session = m::mock());
        $session->shouldReceive('put')->with('authRequest', $authRequest);
        $request->shouldReceive('user')->andReturn($user = m::mock());

        $authRequest->shouldReceive('getClient->getIdentifier')->andReturn(1);
        $authRequest->shouldReceive('getScopes')->andReturn([new Scope('scope-1')]);

        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('find')->with(1)->andReturn($client = m::mock(Client::class));

        $client->shouldReceive('skipsAuthorization')->andReturn(false);

        $response->shouldReceive('view')->once()->andReturnUsing(function ($view, $data) use ($authRequest, $client, $user) {
            $this->assertEquals('passport::authorize', $view);
            $this->assertEquals($client, $data['client']);
            $this->assertEquals($user, $data['user']);
            $this->assertEquals('description', $data['scopes'][0]->description);

            return 'view';
        });

        $tokens = m::mock(TokenRepository::class);
        $tokens->shouldReceive('findValidToken')->with($user, $client)->andReturnNull();

        $this->assertEquals('view', $controller->authorize(
            m::mock(ServerRequestInterface::class), $request, $clients, $tokens
        ));
    }

    public function test_authorization_exceptions_are_handled()
    {
        Container::getInstance()->instance(ExceptionHandler::class, $exceptions = m::mock());
        Container::getInstance()->instance(Repository::class, $config = m::mock());
        $exceptions->shouldReceive('report')->once();
        $config->shouldReceive('get')->once()->andReturn(true);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(ResponseFactory::class);

        $controller = new AuthorizationController($server, $response);

        $server->shouldReceive('validateAuthorizationRequest')->andThrow(new Exception('whoops'));

        $request = m::mock(Request::class);
        $request->shouldReceive('session')->andReturn($session = m::mock());

        $clients = m::mock(ClientRepository::class);
        $tokens = m::mock(TokenRepository::class);

        $this->assertEquals('whoops', $controller->authorize(
            m::mock(ServerRequestInterface::class), $request, $clients, $tokens
        )->getContent());
    }

    public function test_request_is_approved_if_valid_token_exists()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(ResponseFactory::class);

        $controller = new AuthorizationController($server, $response);
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

        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('find')->with(1)->andReturn('client');

        $tokens = m::mock(TokenRepository::class);
        $tokens->shouldReceive('findValidToken')
            ->with($user, 'client')
            ->andReturn($token = m::mock(Token::class));
        $token->shouldReceive('getAttribute')->with('scopes')->andReturn(['scope-1']);

        $this->assertEquals('approved', $controller->authorize(
            m::mock(ServerRequestInterface::class), $request, $clients, $tokens
        )->getContent());
    }

    public function test_request_is_approved_if_client_can_skip_authorization()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(ResponseFactory::class);

        $controller = new AuthorizationController($server, $response);
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

        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('find')->with(1)->andReturn($client = m::mock(Client::class));

        $client->shouldReceive('skipsAuthorization')->andReturn(true);

        $tokens = m::mock(TokenRepository::class);
        $tokens->shouldReceive('findValidToken')
            ->with($user, $client)
            ->andReturnNull();

        $this->assertEquals('approved', $controller->authorize(
            m::mock(ServerRequestInterface::class), $request, $clients, $tokens
        )->getContent());
    }
}
