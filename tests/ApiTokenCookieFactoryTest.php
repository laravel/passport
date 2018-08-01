<?php

use PHPUnit\Framework\TestCase;
use Illuminate\Encryption\Encrypter;
use Laravel\Passport\ApiTokenCookieFactory;

class ApiTokenCookieFactoryTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function test_cookie_can_be_successfully_created()
    {
        $config = Mockery::mock('Illuminate\Contracts\Config\Repository');
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

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie);
    }
}
