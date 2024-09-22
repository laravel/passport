<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Laravel\Passport\Bridge\Scope;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Contracts\AuthorizationViewResponse;
use Laravel\Passport\Exceptions\AuthenticationException;
use Laravel\Passport\Exceptions\OAuthServerException;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException as LeagueException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthorizationControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_authorization_view_is_presented()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(AuthorizationViewResponse::class);
        $guard = m::mock(StatefulGuard::class);

        $controller = new AuthorizationController($server, $guard, $response);

        $guard->shouldReceive('guest')->andReturn(false);
        $guard->shouldReceive('user')->andReturn($user = m::mock(Authenticatable::class));
        $server->shouldReceive('validateAuthorizationRequest')->andReturn($authRequest = m::mock(AuthorizationRequestInterface::class));

        $request = m::mock(Request::class);
        $request->shouldReceive('session')->andReturn($session = m::mock());
        $session->shouldReceive('put')->withSomeOfArgs('authToken');
        $session->shouldReceive('put')->with('authRequest', $authRequest);
        $session->shouldReceive('forget')->with('promptedForLogin')->once();
        $request->shouldReceive('get')->with('prompt')->andReturn(null);

        $authRequest->shouldReceive('getClient->getIdentifier')->andReturn(1);
        $authRequest->shouldReceive('getScopes')->andReturn([new Scope('scope-1')]);

        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('find')->with(1)->andReturn($client = m::mock(Client::class));
        $client->shouldReceive('skipsAuthorization')->andReturn(false);
        $client->shouldReceive('tokens->where->pluck')->andReturn(collect());

        $user->shouldReceive('getAuthIdentifier')->andReturn(1);

        $response->shouldReceive('withParameters')->once()->andReturnUsing(function ($data) use ($client, $user, $request, $response) {
            $this->assertEquals($client, $data['client']);
            $this->assertEquals($user, $data['user']);
            $this->assertEquals($request, $data['request']);
            $this->assertSame('description', $data['scopes'][0]->description);

            return $response;
        });

        $this->assertSame($response, $controller->authorize(
            m::mock(ServerRequestInterface::class), $request, $clients
        ));
    }

    public function test_authorization_exceptions_are_handled()
    {
        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(AuthorizationViewResponse::class);
        $guard = m::mock(StatefulGuard::class);

        $controller = new AuthorizationController($server, $guard, $response);

        $guard->shouldReceive('guest')->andReturn(false);
        $server->shouldReceive('validateAuthorizationRequest')->andThrow(LeagueException::invalidCredentials());

        $request = m::mock(Request::class);
        $request->shouldReceive('session')->andReturn($session = m::mock());

        $clients = m::mock(ClientRepository::class);

        $this->expectException(OAuthServerException::class);

        $controller->authorize(
            m::mock(ServerRequestInterface::class), $request, $clients
        );
    }

    public function test_request_is_approved_if_valid_token_exists()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(AuthorizationViewResponse::class);
        $guard = m::mock(StatefulGuard::class);

        $controller = new AuthorizationController($server, $guard, $response);

        $guard->shouldReceive('guest')->andReturn(false);
        $guard->shouldReceive('user')->andReturn($user = m::mock(Authenticatable::class));
        $psrResponse = new Response();
        $psrResponse->getBody()->write('approved');
        $server->shouldReceive('validateAuthorizationRequest')
            ->andReturn($authRequest = m::mock(AuthorizationRequest::class));
        $server->shouldReceive('completeAuthorizationRequest')
            ->with($authRequest, m::type(ResponseInterface::class))
            ->andReturn($psrResponse);

        $request = m::mock(Request::class);
        $request->shouldReceive('session')->andReturn($session = m::mock());
        $session->shouldReceive('forget')->with('promptedForLogin')->once();
        $user->shouldReceive('getAuthIdentifier')->andReturn(1);
        $request->shouldNotReceive('session');
        $request->shouldReceive('get')->with('prompt')->andReturn(null);

        $authRequest->shouldReceive('getClient->getIdentifier')->once()->andReturn(1);
        $authRequest->shouldReceive('getScopes')->once()->andReturn([new Scope('scope-1')]);
        $authRequest->shouldReceive('setUser')->once()->andReturnNull();
        $authRequest->shouldReceive('setAuthorizationApproved')->once()->with(true);

        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('find')->with(1)->andReturn($client = m::mock(Client::class));

        $client->shouldReceive('skipsAuthorization')->andReturn(false);
        $client->shouldReceive('getKey')->andReturn(1);
        $client->shouldReceive('tokens->where->pluck')->andReturn(collect([['scope-1']]));

        $this->assertSame('approved', $controller->authorize(
            m::mock(ServerRequestInterface::class), $request, $clients
        )->getContent());
    }

    public function test_request_is_approved_if_client_can_skip_authorization()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(AuthorizationViewResponse::class);
        $guard = m::mock(StatefulGuard::class);

        $controller = new AuthorizationController($server, $guard, $response);

        $guard->shouldReceive('guest')->andReturn(false);
        $guard->shouldReceive('user')->andReturn($user = m::mock(Authenticatable::class));
        $psrResponse = new Response();
        $psrResponse->getBody()->write('approved');
        $server->shouldReceive('validateAuthorizationRequest')
            ->andReturn($authRequest = m::mock(AuthorizationRequest::class));
        $server->shouldReceive('completeAuthorizationRequest')
            ->with($authRequest, m::type(ResponseInterface::class))
            ->andReturn($psrResponse);

        $request = m::mock(Request::class);
        $request->shouldReceive('session')->andReturn($session = m::mock());
        $session->shouldReceive('forget')->with('promptedForLogin')->once();
        $user->shouldReceive('getAuthIdentifier')->andReturn(1);
        $request->shouldNotReceive('session');
        $request->shouldReceive('get')->with('prompt')->andReturn(null);

        $authRequest->shouldReceive('getClient->getIdentifier')->once()->andReturn(1);
        $authRequest->shouldReceive('getScopes')->once()->andReturn([new Scope('scope-1')]);
        $authRequest->shouldReceive('setUser')->once()->andReturnNull();
        $authRequest->shouldReceive('setAuthorizationApproved')->once()->with(true);

        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('find')->with(1)->andReturn($client = m::mock(Client::class));

        $client->shouldReceive('skipsAuthorization')->andReturn(true);

        $this->assertSame('approved', $controller->authorize(
            m::mock(ServerRequestInterface::class), $request, $clients
        )->getContent());
    }

    public function test_authorization_view_is_presented_if_request_has_prompt_equals_to_consent()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(AuthorizationViewResponse::class);
        $guard = m::mock(StatefulGuard::class);

        $controller = new AuthorizationController($server, $guard, $response);

        $guard->shouldReceive('guest')->andReturn(false);
        $guard->shouldReceive('user')->andReturn($user = m::mock());
        $server->shouldReceive('validateAuthorizationRequest')
            ->andReturn($authRequest = m::mock(AuthorizationRequest::class));

        $request = m::mock(Request::class);
        $request->shouldReceive('session')->andReturn($session = m::mock());
        $session->shouldReceive('put')->withSomeOfArgs('authToken');
        $session->shouldReceive('put')->with('authRequest', $authRequest);
        $session->shouldReceive('forget')->with('promptedForLogin')->once();
        $request->shouldReceive('get')->with('prompt')->andReturn('consent');

        $authRequest->shouldReceive('getClient->getIdentifier')->once()->andReturn(1);
        $authRequest->shouldReceive('getScopes')->once()->andReturn([new Scope('scope-1')]);

        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('find')->with(1)->andReturn($client = m::mock(Client::class));
        $client->shouldReceive('skipsAuthorization')->andReturn(false);

        $response->shouldReceive('withParameters')->once()->andReturnUsing(function ($data) use ($client, $user, $request, $response) {
            $this->assertEquals($client, $data['client']);
            $this->assertEquals($user, $data['user']);
            $this->assertEquals($request, $data['request']);
            $this->assertSame('description', $data['scopes'][0]->description);

            return $response;
        });

        $this->assertSame($response, $controller->authorize(
            m::mock(ServerRequestInterface::class), $request, $clients
        ));
    }

    public function test_authorization_denied_if_request_has_prompt_equals_to_none()
    {
        $this->expectException('Laravel\Passport\Exceptions\OAuthServerException');

        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(AuthorizationViewResponse::class);
        $guard = m::mock(StatefulGuard::class);

        $controller = new AuthorizationController($server, $guard, $response);

        $guard->shouldReceive('guest')->andReturn(false);
        $guard->shouldReceive('user')->andReturn($user = m::mock(Authenticatable::class));
        $server->shouldReceive('validateAuthorizationRequest')
            ->andReturn($authRequest = m::mock(AuthorizationRequest::class));
        $server->shouldReceive('completeAuthorizationRequest')
            ->with($authRequest, m::type(ResponseInterface::class))
            ->once()
            ->andReturnUsing(function () {
                throw new \League\OAuth2\Server\Exception\OAuthServerException('', 0, '');
            });

        $request = m::mock(Request::class);
        $request->shouldReceive('session')->andReturn($session = m::mock());
        $session->shouldReceive('forget')->with('promptedForLogin')->once();
        $user->shouldReceive('getAuthIdentifier')->andReturn(1);
        $request->shouldReceive('get')->with('prompt')->andReturn('none');

        $authRequest->shouldReceive('getClient->getIdentifier')->once()->andReturn(1);
        $authRequest->shouldReceive('getScopes')->once()->andReturn([new Scope('scope-1')]);
        $authRequest->shouldReceive('setUser')->once()->andReturnNull();
        $authRequest->shouldReceive('setAuthorizationApproved')->once()->with(false);

        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('find')->with(1)->andReturn($client = m::mock(Client::class));
        $client->shouldReceive('skipsAuthorization')->andReturn(false);
        $client->shouldReceive('getKey')->andReturn(1);
        $client->shouldReceive('tokens->where->pluck')->andReturn(collect());

        $controller->authorize(
            m::mock(ServerRequestInterface::class), $request, $clients
        );
    }

    public function test_authorization_denied_if_unauthenticated_and_request_has_prompt_equals_to_none()
    {
        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(AuthorizationViewResponse::class);
        $guard = m::mock(StatefulGuard::class);

        $controller = new AuthorizationController($server, $guard, $response);

        $guard->shouldReceive('guest')->andReturn(true);
        $server->shouldReceive('validateAuthorizationRequest')
            ->andReturn($authRequest = m::mock(AuthorizationRequest::class));
        $server->shouldNotReceive('completeAuthorizationRequest');

        $request = m::mock(Request::class);
        $request->shouldNotReceive('user');
        $request->shouldReceive('get')->with('prompt')->andReturn('none');

        $authRequest->shouldNotReceive('setUser');
        $authRequest->shouldReceive('setAuthorizationApproved')->with(false);
        $authRequest->shouldReceive('getRedirectUri')->andReturn('http://localhost');
        $authRequest->shouldReceive('getClient->getRedirectUri')->andReturn('http://localhost');
        $authRequest->shouldReceive('getState')->andReturn('state');
        $authRequest->shouldReceive('getGrantTypeId')->andReturn('authorization_code');

        $clients = m::mock(ClientRepository::class);

        try {
            $controller->authorize(
                m::mock(ServerRequestInterface::class), $request, $clients
            );
        } catch (\Laravel\Passport\Exceptions\OAuthServerException $e) {
            $this->assertStringStartsWith(
                'http://localhost?state=state&error=access_denied&error_description=',
                $e->getResponse()->headers->get('location')
            );
        }
    }

    public function test_logout_and_prompt_login_if_request_has_prompt_equals_to_login()
    {
        $this->expectException(AuthenticationException::class);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(AuthorizationViewResponse::class);
        $guard = m::mock(StatefulGuard::class);

        $controller = new AuthorizationController($server, $guard, $response);

        $guard->shouldReceive('guest')->andReturn(false);
        $server->shouldReceive('validateAuthorizationRequest')->once();
        $guard->shouldReceive('logout')->once();

        $request = m::mock(Request::class);
        $request->shouldReceive('session')->andReturn($session = m::mock());
        $session->shouldReceive('invalidate')->once();
        $session->shouldReceive('regenerateToken')->once();
        $session->shouldReceive('get')->with('promptedForLogin', false)->once()->andReturn(false);
        $session->shouldReceive('put')->with('promptedForLogin', true)->once();
        $session->shouldNotReceive('forget')->with('promptedForLogin');
        $request->shouldReceive('get')->with('prompt')->andReturn('login');

        $clients = m::mock(ClientRepository::class);

        $controller->authorize(
            m::mock(ServerRequestInterface::class), $request, $clients
        );
    }

    public function test_user_should_be_authenticated()
    {
        $this->expectException(AuthenticationException::class);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(AuthorizationViewResponse::class);
        $guard = m::mock(StatefulGuard::class);

        $controller = new AuthorizationController($server, $guard, $response);

        $guard->shouldReceive('guest')->andReturn(true);
        $server->shouldReceive('validateAuthorizationRequest')->once();

        $request = m::mock(Request::class);
        $request->shouldNotReceive('user');
        $request->shouldReceive('session')->andReturn($session = m::mock());
        $session->shouldReceive('put')->with('promptedForLogin', true)->once();
        $session->shouldNotReceive('forget')->with('promptedForLogin');
        $request->shouldReceive('get')->with('prompt')->andReturn(null);

        $clients = m::mock(ClientRepository::class);

        $controller->authorize(
            m::mock(ServerRequestInterface::class), $request, $clients
        );
    }
}
