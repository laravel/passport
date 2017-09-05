<?php

use Illuminate\Contracts\Routing\ResponseFactory;

class DenyAuthorizationControllerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function test_authorization_can_be_denied()
    {
        $response = Mockery::mock(ResponseFactory::class);

        $controller = new Laravel\Passport\Http\Controllers\DenyAuthorizationController($response);

        $request = Mockery::mock('Illuminate\Http\Request');

        $request->shouldReceive('session')->andReturn($session = Mockery::mock());
        $request->shouldReceive('user')->andReturn(new DenyAuthorizationControllerFakeUser);
        $request->shouldReceive('input')->with('state')->andReturn('state');

        $session->shouldReceive('get')->once()->with('authRequest')->andReturn($authRequest = Mockery::mock(
            'League\OAuth2\Server\RequestTypes\AuthorizationRequest'
        ));

        $authRequest->shouldReceive('setUser')->once();
        $authRequest->shouldReceive('getGrantTypeId')->andReturn('authorization_code');
        $authRequest->shouldReceive('setAuthorizationApproved')->once()->with(true);
        $authRequest->shouldReceive('getClient->getRedirectUri')->andReturn('http://localhost');

        $response->shouldReceive('redirectTo')->once()->andReturnUsing(function ($url) {
            return $url;
        });

        $this->assertEquals('http://localhost?error=access_denied&state=state', $controller->deny($request));
    }

    public function test_authorization_can_be_denied_with_multiple_redirect_uris()
    {
        $response = Mockery::mock(ResponseFactory::class);

        $controller = new Laravel\Passport\Http\Controllers\DenyAuthorizationController($response);

        $request = Mockery::mock('Illuminate\Http\Request');

        $request->shouldReceive('session')->andReturn($session = Mockery::mock());
        $request->shouldReceive('user')->andReturn(new DenyAuthorizationControllerFakeUser);
        $request->shouldReceive('input')->with('state')->andReturn('state');

        $session->shouldReceive('get')->once()->with('authRequest')->andReturn($authRequest = Mockery::mock(
            'League\OAuth2\Server\RequestTypes\AuthorizationRequest'
        ));

        $authRequest->shouldReceive('setUser')->once();
        $authRequest->shouldReceive('getGrantTypeId')->andReturn('authorization_code');
        $authRequest->shouldReceive('setAuthorizationApproved')->once()->with(true);
        $authRequest->shouldReceive('getClient->getRedirectUri')->andReturn(['http://localhost']);

        $response->shouldReceive('redirectTo')->once()->andReturnUsing(function ($url) {
            return $url;
        });

        $this->assertEquals('http://localhost?error=access_denied&state=state', $controller->deny($request));
    }

    public function test_authorization_can_be_denied_implicit()
    {
        $response = Mockery::mock(ResponseFactory::class);

        $controller = new Laravel\Passport\Http\Controllers\DenyAuthorizationController($response);

        $request = Mockery::mock('Illuminate\Http\Request');

        $request->shouldReceive('session')->andReturn($session = Mockery::mock());
        $request->shouldReceive('user')->andReturn(new DenyAuthorizationControllerFakeUser);
        $request->shouldReceive('input')->with('state')->andReturn('state');

        $session->shouldReceive('get')->once()->with('authRequest')->andReturn($authRequest = Mockery::mock(
            'League\OAuth2\Server\RequestTypes\AuthorizationRequest'
        ));

        $authRequest->shouldReceive('setUser')->once();
        $authRequest->shouldReceive('getGrantTypeId')->andReturn('implicit');
        $authRequest->shouldReceive('setAuthorizationApproved')->once()->with(true);
        $authRequest->shouldReceive('getClient->getRedirectUri')->andReturn('http://localhost');

        $response->shouldReceive('redirectTo')->once()->andReturnUsing(function ($url) {
            return $url;
        });

        $this->assertEquals('http://localhost#error=access_denied&state=state', $controller->deny($request));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Authorization request was not present in the session.
     */
    public function test_auth_request_should_exist()
    {
        $response = Mockery::mock(ResponseFactory::class);

        $controller = new Laravel\Passport\Http\Controllers\DenyAuthorizationController($response);

        $request = Mockery::mock('Illuminate\Http\Request');

        $request->shouldReceive('session')->andReturn($session = Mockery::mock());
        $request->shouldReceive('user')->never();
        $request->shouldReceive('input')->never();

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
