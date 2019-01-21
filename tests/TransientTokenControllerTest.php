<?php

namespace Laravel\Passport\Tests;

use Mockery as m;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\ApiTokenCookieFactory;
use Symfony\Component\HttpFoundation\Cookie;
use Laravel\Passport\Http\Controllers\TransientTokenController;

class TransientTokenControllerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_token_can_be_refreshed()
    {
        $cookieFactory = m::mock(ApiTokenCookieFactory::class);
        $cookieFactory->shouldReceive('make')->once()->with(1, 'token')->andReturn(new Cookie('cookie'));

        $request = m::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user = m::mock());
        $user->shouldReceive('getKey')->andReturn(1);
        $request->shouldReceive('session->token')->andReturn('token');

        $controller = new TransientTokenController($cookieFactory);

        $response = $controller->refresh($request);

        $this->assertEquals(200, $response->status());
        $this->assertEquals('Refreshed.', $response->getOriginalContent());
    }
}
