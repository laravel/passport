<?php

namespace Laravel\Passport\Tests;

use Laravel\Passport\Http\Middleware\CheckForAnyScope as CheckScopes;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class CheckForAnyScopeTest extends TestCase
{
    protected function tearDown(): void
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
        $user->shouldReceive('tokenCan')->with('bar')->andReturn(false);

        $response = $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');

        $this->assertEquals('response', $response);
    }

    public function test_exception_is_thrown_if_token_doesnt_have_scope()
    {
        $this->expectException('Laravel\Passport\Exceptions\MissingScopeException');

        $middleware = new CheckScopes;
        $request = m::mock();
        $request->shouldReceive('user')->andReturn($user = m::mock());
        $user->shouldReceive('token')->andReturn($token = m::mock());
        $user->shouldReceive('tokenCan')->with('foo')->andReturn(false);
        $user->shouldReceive('tokenCan')->with('bar')->andReturn(false);

        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }

    public function test_exception_is_thrown_if_no_authenticated_user()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');

        $middleware = new CheckScopes;
        $request = m::mock();
        $request->shouldReceive('user')->once()->andReturn(null);

        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }

    public function test_exception_is_thrown_if_no_token()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');

        $middleware = new CheckScopes;
        $request = m::mock();
        $request->shouldReceive('user')->andReturn($user = m::mock());
        $user->shouldReceive('token')->andReturn(null);

        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }
}
