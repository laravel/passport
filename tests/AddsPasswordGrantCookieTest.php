<?php


use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Encryption\Encrypter;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Passport\Http\Middleware\AddsPasswordGrantCookie;

class AddsPasswordGrantCookieTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function test_it_sets_a_secure_cookie_on_a_response()
    {
        $config = Mockery::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')->with('session')->andReturn([
            'lifetime' => 120,
            'path' => '/',
            'domain' => null,
            'secure' => true,
        ]);

        $request = Request::create('/');
        $expiry = Carbon::now()->addMinutes(10)->getTimestamp();
        $encrypter = new Encrypter(str_repeat('a', 16));

        $middleware = new AddsPasswordGrantCookie($config, $encrypter);

        $response = $middleware->handle($request, function () use ($expiry) {
            $token = JWT::encode([
                'sub' => 1,
                'expiry' => $expiry,
            ], str_repeat('a', 16));
            $expiry = Carbon::now()->addMinutes(10)->getTimestamp();

            return (new Response())->setContent(json_encode(['expires_in' => $expiry, 'access_token' => $token]));
        });

        $cookie = $response->headers->getCookies()[0];
        $decoded = JWT::decode($encrypter->decrypt($cookie->getValue()), str_repeat('a', 16), ['HS256']);

        $this->assertEquals(1, $decoded->sub);
        $this->assertTrue($cookie->isSecure());
        $this->assertEquals($expiry, $decoded->expiry);
    }
}