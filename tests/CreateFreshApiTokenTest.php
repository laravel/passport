<?php

namespace Laravel\Passport\Tests;

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\Passport;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\ApiTokenCookieFactory;
use Laravel\Passport\Http\Middleware\CreateFreshApiToken;
use Symfony\Component\HttpFoundation\Cookie;

class CreateFreshApiTokenTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testShouldReceiveAFreshToken()
    {
        $cookieFactory = m::mock(ApiTokenCookieFactory::class);

        $middleware = new CreateFreshApiToken($cookieFactory);
        $request = m::mock(Request::class)->makePartial();

        $response = new Response;

        $guard = 'guard';
        $user = m::mock()
            ->shouldReceive('getKey')
            ->andReturn($userKey = 1)
            ->getMock();

        $request->shouldReceive('session')->andReturn($session = m::mock());
        $request->shouldReceive('isMethod')->with('GET')->once()->andReturn(true);
        $request->shouldReceive('user')->with($guard)->twice()->andReturn($user);
        $session->shouldReceive('token')->withNoArgs()->once()->andReturn($token = 't0k3n');

        $cookieFactory->shouldReceive('make')
            ->with($userKey, $token)
            ->once()
            ->andReturn(new Cookie(Passport::cookie()));

        $result = $middleware->handle($request, function () use ($response) {
            return $response;
        }, $guard);

        $this->assertSame($response, $result);
        $this->assertTrue($this->hasPassportCookie($response));
    }

    public function testShouldNotReceiveAFreshTokenForOtherHttpVerbs()
    {
        $cookieFactory = m::mock(ApiTokenCookieFactory::class);

        $middleware = new CreateFreshApiToken($cookieFactory);
        $request = Request::create('/', 'POST');
        $response = new Response;

        $result = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        $this->assertSame($response, $result);
        $this->assertFalse($this->hasPassportCookie($response));
    }

    public function testShouldNotReceiveAFreshTokenForAnInvalidUser()
    {
        $cookieFactory = m::mock(ApiTokenCookieFactory::class);

        $middleware = new CreateFreshApiToken($cookieFactory);
        $request = Request::create('/', 'GET');
        $response = new Response;

        $request->setUserResolver(function () {});

        $result = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        $this->assertSame($response, $result);
        $this->assertFalse($this->hasPassportCookie($response));
    }

    public function testShouldNotReceiveAFreshTokenForResponseThatAlreadyHasToken()
    {
        $cookieFactory = m::mock(ApiTokenCookieFactory::class);

        $middleware = new CreateFreshApiToken($cookieFactory);
        $request = Request::create('/', 'GET');

        $response = (new Response)->withCookie(
            new Cookie(Passport::cookie())
        );

        $request->setUserResolver(function () {
            return m::mock()
                ->shouldReceive('getKey')
                ->andReturn(1)
                ->getMock();
        });

        $result = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        $this->assertSame($response, $result);
        $this->assertTrue($this->hasPassportCookie($response));
    }

    protected function hasPassportCookie($response)
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === Passport::cookie()) {
                return true;
            }
        }

        return false;
    }
}
