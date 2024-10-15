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

        $guard->shouldReceive('guest')->andReturn(false);
        $guard->shouldReceive('user')->andReturn($user = m::mock(Authenticatable::class));
        $server->shouldReceive('validateAuthorizationRequest')->andReturn($authRequest = m::mock(AuthorizationRequestInterface::class));

        $psrRequest = m::mock(ServerRequestInterface::class);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);

        $request = m::mock(Request::class);
        $request->shouldReceive('session')->andReturn($session = m::mock());
        $session->shouldReceive('put')->withSomeOfArgs('authToken');
        $session->shouldReceive('put')->with('authRequest', $authRequest);
        $session->shouldReceive('forget')->with('promptedForLogin')->once();
        $request->shouldReceive('get')->with('prompt')->andReturn(null);

        $authRequest->shouldReceive('getClient->getIdentifier')->andReturn(1);
        $authRequest->shouldReceive('getScopes')->andReturn([new Scope('scope-1')]);
        $authRequest->shouldReceive('setUser')->once();

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

        $psrResponse = m::mock(ResponseInterface::class);

        $controller = new AuthorizationController($server, $guard, $clients);

        $this->assertSame($response, $controller->authorize($psrRequest, $request, $psrResponse, $response));
    }

    public function test_authorization_exceptions_are_handled()
    {
        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(AuthorizationViewResponse::class);
        $guard = m::mock(StatefulGuard::class);

        $guard->shouldReceive('guest')->andReturn(false);
        $server->shouldReceive('validateAuthorizationRequest')->andThrow(LeagueException::invalidCredentials());

        $psrRequest = m::mock(ServerRequestInterface::class);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);

        $psrResponse = m::mock(ResponseInterface::class);
        app()->instance(ResponseInterface::class, new Response);

        $request = m::mock(Request::class);

        $clients = m::mock(ClientRepository::class);

        $this->expectException(OAuthServerException::class);

        $controller = new AuthorizationController($server, $guard, $clients);

        $controller->authorize($psrRequest, $request, $psrResponse, $response);
    }

    public function test_request_is_approved_if_valid_token_exists()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(AuthorizationViewResponse::class);
        $guard = m::mock(StatefulGuard::class);

        $guard->shouldReceive('guest')->andReturn(false);
        $guard->shouldReceive('user')->andReturn($user = m::mock(Authenticatable::class));
        $psrResponse = new Response();
        $psrResponse->getBody()->write('approved');
        $server->shouldReceive('validateAuthorizationRequest')
            ->andReturn($authRequest = m::mock(AuthorizationRequest::class));
        $server->shouldReceive('completeAuthorizationRequest')
            ->with($authRequest, m::type(ResponseInterface::class))
            ->andReturn($psrResponse);

        $psrRequest = m::mock(ServerRequestInterface::class);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);

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
        $authRequest->shouldReceive('getGrantTypeId')->once()->andReturn('authorization_code');

        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('find')->with(1)->andReturn($client = m::mock(Client::class));

        $client->shouldReceive('skipsAuthorization')->andReturn(false);
        $client->shouldReceive('getKey')->andReturn(1);
        $client->shouldReceive('tokens->where->pluck')->andReturn(collect([['scope-1']]));

        $controller = new AuthorizationController($server, $guard, $clients);

        $this->assertSame('approved', $controller->authorize($psrRequest, $request, $psrResponse, $response)->getContent());
    }

    public function test_request_is_approved_if_client_can_skip_authorization()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(AuthorizationViewResponse::class);
        $guard = m::mock(StatefulGuard::class);

        $guard->shouldReceive('guest')->andReturn(false);
        $guard->shouldReceive('user')->andReturn($user = m::mock(Authenticatable::class));
        $psrResponse = new Response();
        $psrResponse->getBody()->write('approved');
        $server->shouldReceive('validateAuthorizationRequest')
            ->andReturn($authRequest = m::mock(AuthorizationRequest::class));
        $server->shouldReceive('completeAuthorizationRequest')
            ->with($authRequest, m::type(ResponseInterface::class))
            ->andReturn($psrResponse);

        $psrRequest = m::mock(ServerRequestInterface::class);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);

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
        $authRequest->shouldReceive('getGrantTypeId')->once()->andReturn('authorization_code');

        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('find')->with(1)->andReturn($client = m::mock(Client::class));

        $client->shouldReceive('skipsAuthorization')->andReturn(true);

        $controller = new AuthorizationController($server, $guard, $clients);

        $this->assertSame('approved', $controller->authorize($psrRequest, $request, $psrResponse, $response)->getContent());
    }

    public function test_authorization_view_is_presented_if_request_has_prompt_equals_to_consent()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(AuthorizationViewResponse::class);
        $guard = m::mock(StatefulGuard::class);

        $guard->shouldReceive('guest')->andReturn(false);
        $guard->shouldReceive('user')->andReturn($user = m::mock(Authenticatable::class));
        $user->shouldReceive('getAuthIdentifier')->andReturn(1);
        $server->shouldReceive('validateAuthorizationRequest')
            ->andReturn($authRequest = m::mock(AuthorizationRequest::class));

        $psrRequest = m::mock(ServerRequestInterface::class);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);

        $psrResponse = m::mock(ResponseInterface::class);

        $request = m::mock(Request::class);
        $request->shouldReceive('session')->andReturn($session = m::mock());
        $session->shouldReceive('put')->withSomeOfArgs('authToken');
        $session->shouldReceive('put')->with('authRequest', $authRequest);
        $session->shouldReceive('forget')->with('promptedForLogin')->once();
        $request->shouldReceive('get')->with('prompt')->andReturn('consent');

        $authRequest->shouldReceive('getClient->getIdentifier')->once()->andReturn(1);
        $authRequest->shouldReceive('getScopes')->once()->andReturn([new Scope('scope-1')]);
        $authRequest->shouldReceive('setUser')->once()->andReturnNull();

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

        $controller = new AuthorizationController($server, $guard, $clients);

        $this->assertSame($response, $controller->authorize($psrRequest, $request, $psrResponse, $response));
    }

    public function test_authorization_denied_if_request_has_prompt_equals_to_none()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(AuthorizationViewResponse::class);
        $guard = m::mock(StatefulGuard::class);

        $guard->shouldReceive('guest')->andReturn(false);
        $guard->shouldReceive('user')->andReturn($user = m::mock(Authenticatable::class));
        $server->shouldReceive('validateAuthorizationRequest')
            ->andReturn($authRequest = m::mock(AuthorizationRequest::class));

        $psrRequest = m::mock(ServerRequestInterface::class);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);

        $psrResponse = m::mock(ResponseInterface::class);
        app()->instance(ResponseInterface::class, new Response);

        $request = m::mock(Request::class);
        $request->shouldReceive('session')->andReturn($session = m::mock());
        $session->shouldReceive('forget')->with('promptedForLogin')->once();
        $user->shouldReceive('getAuthIdentifier')->andReturn(1);
        $request->shouldReceive('get')->with('prompt')->andReturn('none');

        $authRequest->shouldReceive('getClient->getIdentifier')->once()->andReturn(1);
        $authRequest->shouldReceive('getScopes')->once()->andReturn([new Scope('scope-1')]);
        $authRequest->shouldReceive('setUser')->once()->andReturnNull();
        $authRequest->shouldReceive('getRedirectUri')->once()->andReturn('http://localhost');
        $authRequest->shouldReceive('getState')->once()->andReturn('state');
        $authRequest->shouldReceive('getGrantTypeId')->once()->andReturn('authorization_code');

        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('find')->with(1)->andReturn($client = m::mock(Client::class));
        $client->shouldReceive('skipsAuthorization')->andReturn(false);
        $client->shouldReceive('getKey')->andReturn(1);
        $client->shouldReceive('tokens->where->pluck')->andReturn(collect());

        $controller = new AuthorizationController($server, $guard, $clients);

        try {
            $controller->authorize($psrRequest, $request, $psrResponse, $response);
        } catch (OAuthServerException $e) {
            $this->assertSame($e->getMessage(), 'The authorization server requires end-user consent.');
            $this->assertStringStartsWith(
                'http://localhost?state=state&error=consent_required&error_description=',
                $e->getResponse()->headers->get('location')
            );

            return;
        }

        $this->expectException(OAuthServerException::class);
    }

    public function test_authorization_denied_if_unauthenticated_and_request_has_prompt_equals_to_none()
    {
        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(AuthorizationViewResponse::class);
        $guard = m::mock(StatefulGuard::class);

        $guard->shouldReceive('guest')->andReturn(true);
        $server->shouldReceive('validateAuthorizationRequest')
            ->andReturn($authRequest = m::mock(AuthorizationRequest::class));
        $server->shouldNotReceive('completeAuthorizationRequest');

        $psrRequest = m::mock(ServerRequestInterface::class);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);

        $psrResponse = m::mock(ResponseInterface::class);
        app()->instance(ResponseInterface::class, new Response);

        $request = m::mock(Request::class);
        $request->shouldNotReceive('user');
        $request->shouldReceive('get')->with('prompt')->andReturn('none');

        $authRequest->shouldNotReceive('setUser');
        $authRequest->shouldReceive('setAuthorizationApproved')->with(false);
        $authRequest->shouldReceive('getRedirectUri')->andReturn('http://localhost');
        $authRequest->shouldReceive('getClient->getRedirectUri')->andReturn('http://localhost');
        $authRequest->shouldReceive('getState')->once()->andReturn('state');
        $authRequest->shouldReceive('getGrantTypeId')->once()->andReturn('authorization_code');

        $clients = m::mock(ClientRepository::class);

        $controller = new AuthorizationController($server, $guard, $clients);

        try {
            $controller->authorize($psrRequest, $request, $psrResponse, $response);
        } catch (OAuthServerException $e) {
            $this->assertSame($e->getMessage(), 'The authorization server requires end-user authentication.');
            $this->assertStringStartsWith(
                'http://localhost?state=state&error=login_required&error_description=',
                $e->getResponse()->headers->get('location')
            );

            return;
        }

        $this->expectException(OAuthServerException::class);
    }

    public function test_logout_and_prompt_login_if_request_has_prompt_equals_to_login()
    {
        $this->expectException(AuthenticationException::class);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(AuthorizationViewResponse::class);
        $guard = m::mock(StatefulGuard::class);

        $guard->shouldReceive('guest')->andReturn(false);
        $server->shouldReceive('validateAuthorizationRequest')->once();
        $guard->shouldReceive('logout')->once();

        $psrRequest = m::mock(ServerRequestInterface::class);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);

        $psrResponse = m::mock(ResponseInterface::class);

        $request = m::mock(Request::class);
        $request->shouldReceive('session')->andReturn($session = m::mock());
        $session->shouldReceive('invalidate')->once();
        $session->shouldReceive('regenerateToken')->once();
        $session->shouldReceive('get')->with('promptedForLogin', false)->once()->andReturn(false);
        $session->shouldReceive('put')->with('promptedForLogin', true)->once();
        $session->shouldNotReceive('forget')->with('promptedForLogin');
        $request->shouldReceive('get')->with('prompt')->andReturn('login');

        $clients = m::mock(ClientRepository::class);

        $controller = new AuthorizationController($server, $guard, $clients);

        $controller->authorize($psrRequest, $request, $psrResponse, $response);
    }

    public function test_user_should_be_authenticated()
    {
        $this->expectException(AuthenticationException::class);

        $server = m::mock(AuthorizationServer::class);
        $response = m::mock(AuthorizationViewResponse::class);
        $guard = m::mock(StatefulGuard::class);

        $guard->shouldReceive('guest')->andReturn(true);
        $server->shouldReceive('validateAuthorizationRequest')->once();

        $psrRequest = m::mock(ServerRequestInterface::class);
        $psrRequest->shouldReceive('getQueryParams')->andReturn([]);

        $psrResponse = m::mock(ResponseInterface::class);

        $request = m::mock(Request::class);
        $request->shouldNotReceive('user');
        $request->shouldReceive('session')->andReturn($session = m::mock());
        $session->shouldReceive('put')->with('promptedForLogin', true)->once();
        $session->shouldNotReceive('forget')->with('promptedForLogin');
        $request->shouldReceive('get')->with('prompt')->andReturn(null);

        $clients = m::mock(ClientRepository::class);

        $controller = new AuthorizationController($server, $guard, $clients);

        $controller->authorize($psrRequest, $request, $psrResponse, $response);
    }
}
