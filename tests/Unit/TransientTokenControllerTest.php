<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Http\Request;
use Laravel\Passport\ApiTokenCookieFactory;
use Laravel\Passport\Http\Controllers\TransientTokenController;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;

class TransientTokenControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_token_can_be_refreshed()
    {
        $cookieFactory = m::mock(ApiTokenCookieFactory::class);
        $cookieFactory->shouldReceive('make')->once()->with(1, 'token')->andReturn(new Cookie('cookie'));

        $request = m::mock(Request::class);
        $request->shouldReceive('user')->andReturn($user = m::mock());
        $user->shouldReceive('getAuthIdentifier')->andReturn(1);
        $request->shouldReceive('session->token')->andReturn('token');

        $controller = new TransientTokenController($cookieFactory);

        $response = $controller->refresh($request);

        $this->assertSame(200, $response->status());
        $this->assertSame('Refreshed.', $response->getOriginalContent());
    }
}
