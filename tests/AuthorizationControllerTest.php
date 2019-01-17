<?php

namespace Laravel\Passport\Tests;

use Mockery as m;
use Zend\Diactoros\Response;
use Laravel\Passport\Passport;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\Bridge\Scope;
use League\OAuth2\Server\AuthorizationServer;
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

        $controller = new AuthorizationController($server, $response);

        $server->shouldReceive('validateAuthorizationRequest')->andReturn($authRequest = m::mock());

        $request = m::mock('Illuminate\Http\Request');
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

        $clients = m::mock('Laravel\Passport\ClientRepository');
        $clients->shouldReceive('find')->with(1)->andReturn('client');

        $tokens = m::mock('Laravel\Passport\TokenRepository');
        $tokens->shouldReceive('findValidToken')->with('user', 'client')->andReturnNull();

        $this->assertEquals('view', $controller->authorize(
            m::mock('Psr\Http\Message\ServerRequestInterface'), $request, $clients, $tokens
        ));
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
            ->andReturn($authRequest = m::mock('League\OAuth2\Server\RequestTypes\AuthorizationRequest'));
        $server->shouldReceive('completeAuthorizationRequest')
            ->with($authRequest, m::type('Psr\Http\Message\ResponseInterface'))
            ->andReturn($psrResponse);

        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('user')->once()->andReturn($user = m::mock());
        $user->shouldReceive('getKey')->andReturn(1);
        $request->shouldNotReceive('session');

        $authRequest->shouldReceive('getClient->getIdentifier')->once()->andReturn(1);
        $authRequest->shouldReceive('getScopes')->once()->andReturn([new Scope('scope-1')]);
        $authRequest->shouldReceive('setUser')->once()->andReturnNull();
        $authRequest->shouldReceive('setAuthorizationApproved')->once()->with(true);

        $clients = m::mock('Laravel\Passport\ClientRepository');
        $clients->shouldReceive('find')->with(1)->andReturn('client');

        $tokens = m::mock('Laravel\Passport\TokenRepository');
        $tokens->shouldReceive('findValidToken')
            ->with($user, 'client')
            ->andReturn($token = m::mock('Laravel\Passport\Token'));
        $token->shouldReceive('getAttribute')->with('scopes')->andReturn(['scope-1']);

        $this->assertEquals('approved', $controller->authorize(
            m::mock('Psr\Http\Message\ServerRequestInterface'), $request, $clients, $tokens
        )->getContent());
    }
}
