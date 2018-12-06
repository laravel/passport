<?php

namespace Laravel\Passport\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\Http\Middleware\CheckScopes;

class CheckScopesTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_request_is_passed_along_if_scopes_are_present_on_token()
    {
        $middleware = new CheckScopes;
        $request = m::mock();
        $request->shouldReceive('user')->andReturn($user = m::mock());
        $user->shouldReceive('token')->andReturn($token = m::mock());
        $user->shouldReceive('tokenCan')->with('foo')->andReturn(true);
        $user->shouldReceive('tokenCan')->with('bar')->andReturn(true);

        $response = $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');

        $this->assertEquals('response', $response);
    }

    /**
     * @expectedException \Laravel\Passport\Exceptions\MissingScopeException
     */
    public function test_exception_is_thrown_if_token_doesnt_have_scope()
    {
        $middleware = new CheckScopes;
        $request = m::mock();
        $request->shouldReceive('user')->andReturn($user = m::mock());
        $user->shouldReceive('token')->andReturn($token = m::mock());
        $user->shouldReceive('tokenCan')->with('foo')->andReturn(false);

        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }

    /**
     * @expectedException \Illuminate\Auth\AuthenticationException
     */
    public function test_exception_is_thrown_if_no_authenticated_user()
    {
        $middleware = new CheckScopes;
        $request = m::mock();
        $request->shouldReceive('user')->once()->andReturn(null);

        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }

    /**
     * @expectedException \Illuminate\Auth\AuthenticationException
     */
    public function test_exception_is_thrown_if_no_token()
    {
        $middleware = new CheckScopes;
        $request = m::mock();
        $request->shouldReceive('user')->andReturn($user = m::mock());
        $user->shouldReceive('token')->andReturn(null);

        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }
}
