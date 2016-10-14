<?php

use Illuminate\Encryption\Encrypter;
use Laravel\Passport\ApiTokenCookieFactory;

class ApiTokenCookieFactoryTest extends PHPUnit_Framework_TestCase
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
        ]);
        $encrypter = new Encrypter(str_repeat('a', 16));
        $factory = new ApiTokenCookieFactory($config, $encrypter);

        $cookie = $factory->make(1, 'token');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie);
        $this->assertEquals('laravel_token', $cookie->getName());
    }

    public function test_cookie_can_be_successfully_created_with_custom_name()
    {
        $custom_cookie_name = 'my_custom_cookie_name';
        $config = Mockery::mock('Illuminate\Contracts\Config\Repository');
        $config->shouldReceive('get')->with('session')->andReturn([
            'passport_cookie' => $custom_cookie_name,
            'lifetime' => 120,
            'path' => '/',
            'domain' => null,
            'secure' => true,
        ]);
        $encrypter = new Encrypter(str_repeat('a', 16));
        $factory = new ApiTokenCookieFactory($config, $encrypter);

        $cookie = $factory->make(1, 'token');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie);
        $this->assertEquals($custom_cookie_name, $cookie->getName());
    }
}
