<?php

namespace Laravel\Passport\Tests\Unit;

use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Matching\Argument;
use JMac\Testing\Double;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\ApiTokenCookieFactory;
use Laravel\Passport\Http\Middleware\CreateFreshApiToken;
use Laravel\Passport\Passport;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;

class CreateFreshApiTokenTest extends TestCase
{
    use VerifiesDoubles;

    public function testShouldReceiveAFreshToken()
    {
        $cookieFactory = Double::for(ApiTokenCookieFactory::class);

        $middleware = new CreateFreshApiToken($cookieFactory);
        $request = Double::for(Request::class)->passthru();

        $response = new Response;

        $guard = 'guard';
        $user = Double::for(\stdClass::class)
            ->shouldReceive('getAuthIdentifier')
            ->andReturn($userKey = 1)
            ->getMock();

        $request->allows('session')->returns($session = Double::for(\stdClass::class));
        $request->expects('isMethod')->with('GET')->returns(true);
        $request->expects('user')->with($guard)->times(2)->returns($user);
        $session->expects('token')->with(Argument::none())->returns($token = 't0k3n');

        $cookieFactory->expects('make')->with($userKey, $token)->returns(new Cookie(Passport::cookie()));

        $result = $middleware->handle($request, function () use ($response) {
            return $response;
        }, $guard);

        $this->assertSame($response, $result);
        $this->assertTrue($this->hasPassportCookie($response));
    }

    public function testShouldNotReceiveAFreshTokenForOtherHttpVerbs()
    {
        $cookieFactory = Double::for(ApiTokenCookieFactory::class);

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
        $cookieFactory = Double::for(ApiTokenCookieFactory::class);

        $middleware = new CreateFreshApiToken($cookieFactory);
        $request = Request::create('/', 'GET');
        $response = new Response;

        $request->setUserResolver(function () {
        });

        $result = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        $this->assertSame($response, $result);
        $this->assertFalse($this->hasPassportCookie($response));
    }

    public function testShouldNotReceiveAFreshTokenForResponseThatAlreadyHasToken()
    {
        $cookieFactory = Double::for(ApiTokenCookieFactory::class);

        $middleware = new CreateFreshApiToken($cookieFactory);
        $request = Request::create('/', 'GET');

        $response = (new Response)->withCookie(
            new Cookie(Passport::cookie())
        );

        $request->setUserResolver(function () {
            return Double::for(\stdClass::class)
                ->shouldReceive('getAuthIdentifier')
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
