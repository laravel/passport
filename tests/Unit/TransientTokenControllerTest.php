<?php

namespace Laravel\Passport\Tests\Unit;

use JMac\Testing\Double;
use Illuminate\Http\Request;
use Laravel\Passport\ApiTokenCookieFactory;
use Laravel\Passport\Http\Controllers\TransientTokenController;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;

class TransientTokenControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_token_can_be_refreshed()
    {
        $cookieFactory = Double::for(ApiTokenCookieFactory::class);
        $cookieFactory->expects('make')->with(1, 'token')->returns(new Cookie('cookie'));

        $request = Double::for(Request::class);
        $request->allows('user')->returns($user = Double::for(\stdClass::class));
        $user->allows('getAuthIdentifier')->returns(1);
        $request->shouldReceive('session->token')->andReturn('token');

        $controller = new TransientTokenController($cookieFactory);

        $response = $controller->refresh($request);

        $this->assertSame(200, $response->status());
        $this->assertSame('Refreshed.', $response->getOriginalContent());
    }
}
