<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Encryption\Encrypter;
use Laravel\Passport\ApiTokenCookieFactory;
use Laravel\Passport\Passport;
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

    public function test_cookie_can_be_successfully_created_when_using_a_custom_encryption_key()
    {
        Passport::encryptTokensUsing(function (EncrypterContract $encrypter) {
            return $encrypter->getKey().'.mykey';
        });

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

        // Revert to the default encryption method
        Passport::encryptTokensUsing(null);
    }
}
