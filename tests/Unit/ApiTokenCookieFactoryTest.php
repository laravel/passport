<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Encryption\Encrypter;
use Laravel\Passport\ApiTokenCookieFactory;
use Laravel\Passport\Passport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;

class ApiTokenCookieFactoryTest extends TestCase
{
    public function test_cookie_can_be_successfully_created()
    {
        $config = new Repository(['session' => [
            'lifetime' => 120,
            'path' => '/',
            'domain' => null,
            'secure' => true,
            'same_site' => 'lax',
        ]]);
        $encrypter = new Encrypter(str_repeat('a', 32), 'aes-256-cbc');
        $factory = new ApiTokenCookieFactory($config, $encrypter);

        $cookie = $factory->make(1, 'token');

        $this->assertInstanceOf(Cookie::class, $cookie);
    }

    public function test_cookie_can_be_successfully_created_when_using_a_custom_encryption_key()
    {
        Passport::encryptTokensUsing(function (EncrypterContract $encrypter) {
            return $encrypter->getKey().'.mykey';
        });

        $config = new Repository(['session' => [
            'lifetime' => 120,
            'path' => '/',
            'domain' => null,
            'secure' => true,
            'same_site' => 'lax',
        ]]);
        $encrypter = new Encrypter(str_repeat('a', 32), 'aes-256-cbc');
        $factory = new ApiTokenCookieFactory($config, $encrypter);

        $cookie = $factory->make(1, 'token');

        $this->assertInstanceOf(Cookie::class, $cookie);

        // Revert to the default encryption method
        Passport::encryptTokensUsing(null);
    }
}
