<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\Http\Middleware\CreateFreshApiToken;
use Laravel\Passport\Passport;

class CreateFreshApiTokenTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testShouldReceiveAFreshToken()
    {
        $cookieFactory = Mockery::mock(\Laravel\Passport\ApiTokenCookieFactory::class);

        $middleware = new CreateFreshApiToken($cookieFactory);
        $request = Mockery::mock(Request::class)->makePartial();

        $response = new Response;

        $guard = 'guard';
        $user = Mockery::mock()
            ->shouldReceive('getKey')
            ->andReturn($userKey = 1)
            ->getMock();

        $request->shouldReceive('session')->andReturn($session = Mockery::mock());
        $request->shouldReceive('isMethod')->with('GET')->once()->andReturn(true);
        $request->shouldReceive('user')->with($guard)->twice()->andReturn($user);
        $session->shouldReceive('token')->withNoArgs()->once()->andReturn($token = 't0k3n');

        $cookieFactory->shouldReceive('make')
            ->with($userKey, $token)
            ->once()
            ->andReturn(new \Symfony\Component\HttpFoundation\Cookie(Passport::cookie()));

        $result = $middleware->handle($request, function () use ($response) {
            return $response;
        }, $guard);

        $this->assertSame($response, $result);
        $this->assertTrue($this->hasPassportCookie($response));
    }

    public function testShouldNotReceiveAFreshTokenForOtherHttpVerbs()
    {
        $cookieFactory = Mockery::mock(\Laravel\Passport\ApiTokenCookieFactory::class);

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
        $cookieFactory = Mockery::mock(\Laravel\Passport\ApiTokenCookieFactory::class);

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
        $cookieFactory = Mockery::mock(\Laravel\Passport\ApiTokenCookieFactory::class);

        $middleware = new CreateFreshApiToken($cookieFactory);
        $request = Request::create('/', 'GET');

        $response = (new Response)->withCookie(
            new \Symfony\Component\HttpFoundation\Cookie(Passport::cookie())
        );

        $request->setUserResolver(function () {
            return Mockery::mock()
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

    private function hasPassportCookie($response)
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === Passport::cookie()) {
                return true;
            }
        }

        return false;
    }
}
