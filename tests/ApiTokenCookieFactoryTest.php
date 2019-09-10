<?php

namespace Laravel\Passport\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Encryption\Encrypter;
use Laravel\Passport\ApiTokenCookieFactory;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;

class ApiTokenCookieFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_cookie_can_be_successfully_created()
    {
        $config = m::mock(Repository::class);
        $config->shouldReceive('get')->with('session')->andReturn([
            'lifetime' => 120,
            'path' => '/',
            'domain' => null,
            'secure' => true,
            'same_site' => 'lax',
        ]);
        $encrypter = new Encrypter(str_repeat('a', 16));
        $factory = new ApiTokenCookieFactory($config, $encrypter);

        $cookie = $factory->make(1, 'token');

        $this->assertInstanceOf(Cookie::class, $cookie);
    }
}
