<?php

use League\OAuth2\Server\AuthorizationServer;

class ApproveAuthorizationControllerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function test_complete_authorization_request()
    {
        $server = Mockery::mock(AuthorizationServer::class);

        $controller = new Laravel\Passport\Http\Controllers\ApproveAuthorizationController($server);

        $request = Mockery::mock('Illuminate\Http\Request');
        $request->shouldReceive('session')->andReturn($session = Mockery::mock());
        $session->shouldReceive('get')->once()->with('authRequest')->andReturn($authRequest = Mockery::mock('League\OAuth2\Server\RequestTypes\AuthorizationRequest'));
        $request->shouldReceive('user')->andReturn(new ApproveAuthorizationControllerFakeUser);
        $authRequest->shouldReceive('getClient->getIdentifier')->andReturn(1);
        $authRequest->shouldReceive('getUser->getIdentifier')->andReturn(2);
        $authRequest->shouldReceive('setUser')->once();
        $authRequest->shouldReceive('setAuthorizationApproved')->once()->with(true);

        $server->shouldReceive('completeAuthorizationRequest')->with($authRequest, Mockery::type('Psr\Http\Message\ResponseInterface'))->andReturn('response');

        $this->assertEquals('response', $controller->approve($request));
    }
}

class ApproveAuthorizationControllerFakeUser
{
    public $id = 1;
    public function getKey()
    {
        return $this->id;
    }
}
