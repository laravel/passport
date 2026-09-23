<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use Laravel\Passport\ApiTokenCookieFactory;
use Laravel\Passport\Http\Controllers\TransientTokenController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;

class TransientTokenControllerTest extends TestCase
{
    use VerifiesDoubles;

    public function test_token_can_be_refreshed()
    {
        $cookieFactory = Double::for(ApiTokenCookieFactory::class);
        $cookieFactory->expects('make')->with(1, 'token')->returns(new Cookie('cookie'));

        $request = Double::for(Request::class, override: true);
        $request->allows('user')->returns($user = Double::for(Authenticatable::class));
        $user->allows('getAuthIdentifier')->returns(1);
        $request->allows('session')->returns($session = Double::for(Session::class));
        $session->allows('token')->returns('token');

        $controller = new TransientTokenController($cookieFactory);

        $response = $controller->refresh($request->instance());

        $this->assertSame(200, $response->status());
        $this->assertSame('Refreshed.', $response->getOriginalContent());
    }
}
