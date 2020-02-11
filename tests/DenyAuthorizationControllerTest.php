<?php

namespace Laravel\Passport\Tests;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\DenyAuthorizationController;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DenyAuthorizationControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_authorization_can_be_denied()
    {
        $response = m::mock(ResponseFactory::class);

        $controller = new DenyAuthorizationController($response);

        $request = m::mock(Request::class);

        $request->shouldReceive('session')->andReturn($session = m::mock());
        $request->shouldReceive('user')->andReturn(new DenyAuthorizationControllerFakeUser);
        $request->shouldReceive('input')->with('state')->andReturn('state');
        $request->shouldReceive('has')->with('auth_token')->andReturn(true);
        $request->shouldReceive('get')->with('auth_token')->andReturn('foo');

        $session->shouldReceive('get')->once()->with('authToken')->andReturn('foo');
        $session->shouldReceive('get')->once()->with('authRequest')->andReturn($authRequest = m::mock(
            AuthorizationRequest::class
        ));

        $authRequest->shouldReceive('setUser')->once();
        $authRequest->shouldReceive('getGrantTypeId')->andReturn('authorization_code');
        $authRequest->shouldReceive('setAuthorizationApproved')->once()->with(true);
        $authRequest->shouldReceive('getRedirectUri')->andReturn('http://localhost');
        $authRequest->shouldReceive('getClient->getRedirectUri')->andReturn('http://localhost');

        $response->shouldReceive('redirectTo')->once()->andReturnUsing(function ($url) {
            return $url;
        });

        $this->assertEquals('http://localhost?error=access_denied&state=state', $controller->deny($request));
    }

    public function test_authorization_can_be_denied_with_multiple_redirect_uris()
    {
        $response = m::mock(ResponseFactory::class);

        $controller = new DenyAuthorizationController($response);

        $request = m::mock(Request::class);

        $request->shouldReceive('session')->andReturn($session = m::mock());
        $request->shouldReceive('user')->andReturn(new DenyAuthorizationControllerFakeUser);
        $request->shouldReceive('input')->with('state')->andReturn('state');
        $request->shouldReceive('has')->with('auth_token')->andReturn(true);
        $request->shouldReceive('get')->with('auth_token')->andReturn('foo');

        $session->shouldReceive('get')->once()->with('authRequest')->andReturn($authRequest = m::mock(
            AuthorizationRequest::class
        ));

        $authRequest->shouldReceive('setUser')->once();
        $authRequest->shouldReceive('getGrantTypeId')->andReturn('authorization_code');
        $authRequest->shouldReceive('setAuthorizationApproved')->once()->with(true);
        $authRequest->shouldReceive('getRedirectUri')->andReturn('http://localhost');
        $authRequest->shouldReceive('getClient->getRedirectUri')->andReturn(['http://localhost.localdomain', 'http://localhost']);

        $session->shouldReceive('get')->once()->with('authToken')->andReturn('foo');
        $response->shouldReceive('redirectTo')->once()->andReturnUsing(function ($url) {
            return $url;
        });

        $this->assertEquals('http://localhost?error=access_denied&state=state', $controller->deny($request));
    }

    public function test_authorization_can_be_denied_implicit()
    {
        $response = m::mock(ResponseFactory::class);

        $controller = new DenyAuthorizationController($response);

        $request = m::mock(Request::class);

        $request->shouldReceive('session')->andReturn($session = m::mock());
        $request->shouldReceive('user')->andReturn(new DenyAuthorizationControllerFakeUser);
        $request->shouldReceive('input')->with('state')->andReturn('state');
        $request->shouldReceive('has')->with('auth_token')->andReturn(true);
        $request->shouldReceive('get')->with('auth_token')->andReturn('foo');

        $session->shouldReceive('get')->once()->with('authToken')->andReturn('foo');
        $session->shouldReceive('get')->once()->with('authRequest')->andReturn($authRequest = m::mock(
            AuthorizationRequest::class
        ));

        $authRequest->shouldReceive('setUser')->once();
        $authRequest->shouldReceive('getGrantTypeId')->andReturn('implicit');
        $authRequest->shouldReceive('setAuthorizationApproved')->once()->with(true);
        $authRequest->shouldReceive('getRedirectUri')->andReturn('http://localhost');
        $authRequest->shouldReceive('getClient->getRedirectUri')->andReturn('http://localhost');

        $response->shouldReceive('redirectTo')->once()->andReturnUsing(function ($url) {
            return $url;
        });

        $this->assertEquals('http://localhost#error=access_denied&state=state', $controller->deny($request));
    }

    public function test_authorization_can_be_denied_with_existing_query_string()
    {
        $response = m::mock(ResponseFactory::class);

        $controller = new DenyAuthorizationController($response);

        $request = m::mock(Request::class);

        $request->shouldReceive('session')->andReturn($session = m::mock());
        $request->shouldReceive('user')->andReturn(new DenyAuthorizationControllerFakeUser);
        $request->shouldReceive('input')->with('state')->andReturn('state');
        $request->shouldReceive('has')->with('auth_token')->andReturn(true);
        $request->shouldReceive('get')->with('auth_token')->andReturn('foo');

        $session->shouldReceive('get')->once()->with('authToken')->andReturn('foo');
        $session->shouldReceive('get')->once()->with('authRequest')->andReturn($authRequest = m::mock(
            AuthorizationRequest::class
        ));

        $authRequest->shouldReceive('setUser')->once();
        $authRequest->shouldReceive('getGrantTypeId')->andReturn('authorization_code');
        $authRequest->shouldReceive('setAuthorizationApproved')->once()->with(true);
        $authRequest->shouldReceive('getRedirectUri')->andReturn('http://localhost?action=some_action');
        $authRequest->shouldReceive('getClient->getRedirectUri')->andReturn('http://localhost?action=some_action');

        $response->shouldReceive('redirectTo')->once()->andReturnUsing(function ($url) {
            return $url;
        });

        $this->assertEquals('http://localhost?action=some_action&error=access_denied&state=state', $controller->deny($request));
    }

    public function test_auth_request_should_exist()
    {
        $this->expectException('Exception');
        $this->expectExceptionMessage('Authorization request was not present in the session.');

        $response = m::mock(ResponseFactory::class);

        $controller = new DenyAuthorizationController($response);

        $request = m::mock(Request::class);

        $request->shouldReceive('session')->andReturn($session = m::mock());
        $request->shouldReceive('user')->never();
        $request->shouldReceive('input')->never();
        $request->shouldReceive('has')->with('auth_token')->andReturn(true);
        $request->shouldReceive('get')->with('auth_token')->andReturn('foo');

        $session->shouldReceive('get')->once()->with('authToken')->andReturn('foo');
        $session->shouldReceive('get')->once()->with('authRequest')->andReturnNull();

        $response->shouldReceive('redirectTo')->never();

        $controller->deny($request);
    }
}

class DenyAuthorizationControllerFakeUser
{
    public $id = 1;

    public function getKey()
    {
        return $this->id;
    }
}
