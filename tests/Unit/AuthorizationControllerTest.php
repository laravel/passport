<?php

namespace Laravel\Passport\Tests\Unit;

use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Matching\Argument;
use JMac\Testing\Double;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Response;

class AuthorizationControllerTest extends TestCase
{
    use VerifiesDoubles;

    public function test_authorization_view_is_presented()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = Double::for(AuthorizationServer::class);
        $response = Double::for(AuthorizationViewResponse::class);
        $guard = Double::for(StatefulGuard::class);

        $authRequest = new AuthorizationRequest;
        $authRequest->setClient(new \Laravel\Passport\Bridge\Client('1', 'Test Client'));
        $authRequest->setScopes([new Scope('scope-1')]);

        $guard->allows('guest')->returns(false);
        $guard->allows('user')->returns($user = Double::for(Authenticatable::class));
        $server->allows('validateAuthorizationRequest')->returns($authRequest);

        $psrRequest = Double::for(ServerRequestInterface::class);
        $psrRequest->allows('getQueryParams')->returns([]);

        $request = Double::for(Request::class);
        $request->allows('session')->returns($session = Double::for(\stdClass::class));
        $session->shouldReceive('put')->withSomeOfArgs('authToken');
        $session->expects('put')->with('authRequest', Argument::satisfies(fn ($value) => is_string($value)));
        $session->expects('forget')->with('promptedForLogin');
        $request->allows('string')->with('prompt')->returns(Str::of(null));

        $clients = Double::for(ClientRepository::class);
        $clients->allows('find')->with(1)->returns($client = Double::for(Client::class));
        $client->allows('skipsAuthorization')->returns(false);
        $client->shouldReceive('tokens->where->pluck')->andReturn(collect());

        $user->allows('getAuthIdentifier')->returns(1);

        $response->expects('withParameters')->resolves(function ($data) use ($client, $user, $request, $response) {
            $this->assertEquals($client, $data['client']);
            $this->assertEquals($user, $data['user']);
            $this->assertEquals($request, $data['request']);
            $this->assertSame('description', $data['scopes'][0]->description);

            return $response;
        });

        $psrResponse = Double::for(ResponseInterface::class);

        $controller = new AuthorizationController($server, $guard, $clients);

        $this->assertSame($response, $controller->authorize($psrRequest, $request, $psrResponse, $response));
    }

    public function test_authorization_exceptions_are_handled()
    {
        $server = Double::for(AuthorizationServer::class);
        $response = Double::for(AuthorizationViewResponse::class);
        $guard = Double::for(StatefulGuard::class);

        $guard->allows('guest')->returns(false);
        $server->allows('validateAuthorizationRequest')->throws(LeagueException::invalidCredentials());

        $psrRequest = Double::for(ServerRequestInterface::class);
        $psrRequest->allows('getQueryParams')->returns([]);

        $psrResponse = Double::for(ResponseInterface::class);
        app()->instance(ResponseInterface::class, (new PsrHttpFactory)->createResponse(new Response));

        $request = Double::for(Request::class);

        $clients = Double::for(ClientRepository::class);

        $this->expectException(OAuthServerException::class);

        $controller = new AuthorizationController($server, $guard, $clients);

        $controller->authorize($psrRequest, $request, $psrResponse, $response);
    }

    public function test_request_is_approved_if_valid_token_exists()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = Double::for(AuthorizationServer::class);
        $response = Double::for(AuthorizationViewResponse::class);
        $guard = Double::for(StatefulGuard::class);

        $guard->allows('guest')->returns(false);
        $guard->allows('user')->returns($user = Double::for(Authenticatable::class));
        $psrResponse = (new PsrHttpFactory)->createResponse(new Response);
        $psrResponse->getBody()->write('approved');
        $server->allows('validateAuthorizationRequest')->returns($authRequest = Double::for(AuthorizationRequest::class));
        $server->allows('completeAuthorizationRequest')->with($authRequest, Argument::type(ResponseInterface::class))->returns($psrResponse);

        $psrRequest = Double::for(ServerRequestInterface::class);
        $psrRequest->allows('getQueryParams')->returns([]);

        $request = Double::for(Request::class);
        $request->allows('session')->returns($session = Double::for(\stdClass::class));
        $session->expects('forget')->with('promptedForLogin');
        $user->allows('getAuthIdentifier')->returns(1);
        $request->expects('session')->never();
        $request->allows('string')->with('prompt')->returns(Str::of(null));

        $authRequest->shouldReceive('getClient->getIdentifier')->once()->andReturn(1);
        $authRequest->expects('getScopes')->returns([new Scope('scope-1')]);
        $authRequest->expects('setUser')->returns(null);
        $authRequest->expects('setAuthorizationApproved')->with(true);
        $authRequest->expects('getGrantTypeId')->returns('authorization_code');

        $clients = Double::for(ClientRepository::class);
        $clients->allows('find')->with(1)->returns($client = Double::for(Client::class));

        $client->allows('skipsAuthorization')->returns(false);
        $client->allows('getKey')->returns(1);
        $client->shouldReceive('tokens->where->pluck')->andReturn(collect([['scope-1']]));

        $controller = new AuthorizationController($server, $guard, $clients);

        $this->assertSame('approved', $controller->authorize($psrRequest, $request, $psrResponse, $response)->getContent());
    }

    public function test_request_is_approved_if_client_can_skip_authorization()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = Double::for(AuthorizationServer::class);
        $response = Double::for(AuthorizationViewResponse::class);
        $guard = Double::for(StatefulGuard::class);

        $guard->allows('guest')->returns(false);
        $guard->allows('user')->returns($user = Double::for(Authenticatable::class));
        $psrResponse = (new PsrHttpFactory)->createResponse(new Response);
        $psrResponse->getBody()->write('approved');
        $server->allows('validateAuthorizationRequest')->returns($authRequest = Double::for(AuthorizationRequest::class));
        $server->allows('completeAuthorizationRequest')->with($authRequest, Argument::type(ResponseInterface::class))->returns($psrResponse);

        $psrRequest = Double::for(ServerRequestInterface::class);
        $psrRequest->allows('getQueryParams')->returns([]);

        $request = Double::for(Request::class);
        $request->allows('session')->returns($session = Double::for(\stdClass::class));
        $session->expects('forget')->with('promptedForLogin');
        $user->allows('getAuthIdentifier')->returns(1);
        $request->expects('session')->never();
        $request->allows('string')->with('prompt')->returns(Str::of(null));

        $authRequest->shouldReceive('getClient->getIdentifier')->once()->andReturn(1);
        $authRequest->expects('getScopes')->returns([new Scope('scope-1')]);
        $authRequest->expects('setUser')->returns(null);
        $authRequest->expects('setAuthorizationApproved')->with(true);
        $authRequest->expects('getGrantTypeId')->returns('authorization_code');

        $clients = Double::for(ClientRepository::class);
        $clients->allows('find')->with(1)->returns($client = Double::for(Client::class));

        $client->allows('skipsAuthorization')->returns(true);

        $controller = new AuthorizationController($server, $guard, $clients);

        $this->assertSame('approved', $controller->authorize($psrRequest, $request, $psrResponse, $response)->getContent());
    }

    public function test_authorization_view_is_presented_if_request_has_prompt_equals_to_consent()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $server = Double::for(AuthorizationServer::class);
        $response = Double::for(AuthorizationViewResponse::class);
        $guard = Double::for(StatefulGuard::class);

        $authRequest = new AuthorizationRequest;
        $authRequest->setClient(new \Laravel\Passport\Bridge\Client('1', 'Test Client'));
        $authRequest->setScopes([new Scope('scope-1')]);

        $guard->allows('guest')->returns(false);
        $guard->allows('user')->returns($user = Double::for(Authenticatable::class));
        $user->allows('getAuthIdentifier')->returns(1);
        $server->allows('validateAuthorizationRequest')->returns($authRequest);

        $psrRequest = Double::for(ServerRequestInterface::class);
        $psrRequest->allows('getQueryParams')->returns([]);

        $psrResponse = Double::for(ResponseInterface::class);

        $request = Double::for(Request::class);
        $request->allows('session')->returns($session = Double::for(\stdClass::class));
        $session->shouldReceive('put')->withSomeOfArgs('authToken');
        $session->expects('put')->with('authRequest', Argument::satisfies(fn ($value) => is_string($value)));
        $session->expects('forget')->with('promptedForLogin');
        $request->allows('string')->with('prompt')->returns(Str::of('consent'));

        $clients = Double::for(ClientRepository::class);
        $clients->allows('find')->with(1)->returns($client = Double::for(Client::class));
        $client->allows('skipsAuthorization')->returns(false);

        $response->expects('withParameters')->resolves(function ($data) use ($client, $user, $request, $response) {
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

        $server = Double::for(AuthorizationServer::class);
        $response = Double::for(AuthorizationViewResponse::class);
        $guard = Double::for(StatefulGuard::class);

        $guard->allows('guest')->returns(false);
        $guard->allows('user')->returns($user = Double::for(Authenticatable::class));
        $server->allows('validateAuthorizationRequest')->returns($authRequest = Double::for(AuthorizationRequest::class));

        $psrRequest = Double::for(ServerRequestInterface::class);
        $psrRequest->allows('getQueryParams')->returns([]);

        $psrResponse = Double::for(ResponseInterface::class);
        app()->instance(ResponseInterface::class, (new PsrHttpFactory)->createResponse(new Response));

        $request = Double::for(Request::class);
        $request->allows('session')->returns($session = Double::for(\stdClass::class));
        $session->expects('forget')->with('promptedForLogin');
        $user->allows('getAuthIdentifier')->returns(1);
        $request->allows('string')->with('prompt')->returns(Str::of('none'));

        $authRequest->shouldReceive('getClient->getIdentifier')->once()->andReturn(1);
        $authRequest->expects('getScopes')->returns([new Scope('scope-1')]);
        $authRequest->expects('setUser')->returns(null);
        $authRequest->expects('getRedirectUri')->returns('http://localhost');
        $authRequest->expects('getState')->returns('state');
        $authRequest->expects('getGrantTypeId')->returns('authorization_code');

        $clients = Double::for(ClientRepository::class);
        $clients->allows('find')->with(1)->returns($client = Double::for(Client::class));
        $client->allows('skipsAuthorization')->returns(false);
        $client->allows('getKey')->returns(1);
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
        $server = Double::for(AuthorizationServer::class);
        $response = Double::for(AuthorizationViewResponse::class);
        $guard = Double::for(StatefulGuard::class);

        $guard->allows('guest')->returns(true);
        $server->allows('validateAuthorizationRequest')->returns($authRequest = Double::for(AuthorizationRequest::class));
        $server->expects('completeAuthorizationRequest')->never();

        $psrRequest = Double::for(ServerRequestInterface::class);
        $psrRequest->allows('getQueryParams')->returns([]);

        $psrResponse = Double::for(ResponseInterface::class);
        app()->instance(ResponseInterface::class, (new PsrHttpFactory)->createResponse(new Response));

        $request = Double::for(Request::class);
        $request->expects('user')->never();
        $request->allows('string')->with('prompt')->returns(Str::of('none'));

        $authRequest->expects('setUser')->never();
        $authRequest->allows('setAuthorizationApproved')->with(false);
        $authRequest->allows('getRedirectUri')->returns('http://localhost');
        $authRequest->shouldReceive('getClient->getRedirectUri')->andReturn('http://localhost');
        $authRequest->expects('getState')->returns('state');
        $authRequest->expects('getGrantTypeId')->returns('authorization_code');

        $clients = Double::for(ClientRepository::class);

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

        $server = Double::for(AuthorizationServer::class);
        $response = Double::for(AuthorizationViewResponse::class);
        $guard = Double::for(StatefulGuard::class);

        $guard->allows('guest')->returns(false);
        $server->expects('validateAuthorizationRequest');
        $guard->expects('logout');

        $psrRequest = Double::for(ServerRequestInterface::class);
        $psrRequest->allows('getQueryParams')->returns([]);

        $psrResponse = Double::for(ResponseInterface::class);

        $request = Double::for(Request::class);
        $request->allows('session')->returns($session = Double::for(\stdClass::class));
        $session->expects('invalidate');
        $session->expects('regenerateToken');
        $session->expects('get')->with('promptedForLogin', false)->returns(false);
        $session->expects('put')->with('promptedForLogin', true);
        $session->expects('forget')->with('promptedForLogin')->never();
        $request->allows('string')->with('prompt')->returns(Str::of('login'));

        $clients = Double::for(ClientRepository::class);

        $controller = new AuthorizationController($server, $guard, $clients);

        $controller->authorize($psrRequest, $request, $psrResponse, $response);
    }

    public function test_user_should_be_authenticated()
    {
        $this->expectException(AuthenticationException::class);

        $server = Double::for(AuthorizationServer::class);
        $response = Double::for(AuthorizationViewResponse::class);
        $guard = Double::for(StatefulGuard::class);

        $guard->allows('guest')->returns(true);
        $server->expects('validateAuthorizationRequest');

        $psrRequest = Double::for(ServerRequestInterface::class);
        $psrRequest->allows('getQueryParams')->returns([]);

        $psrResponse = Double::for(ResponseInterface::class);

        $request = Double::for(Request::class);
        $request->expects('user')->never();
        $request->allows('session')->returns($session = Double::for(\stdClass::class));
        $session->expects('put')->with('promptedForLogin', true);
        $session->expects('forget')->with('promptedForLogin')->never();
        $request->allows('string')->with('prompt')->returns(Str::of(null));

        $clients = Double::for(ClientRepository::class);

        $controller = new AuthorizationController($server, $guard, $clients);

        $controller->authorize($psrRequest, $request, $psrResponse, $response);
    }
}
