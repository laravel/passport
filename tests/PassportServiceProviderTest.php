<?php

namespace Laravel\Passport\Tests;

use Mockery as m;
use Laravel\Passport\Passport;
use PHPUnit\Framework\TestCase;
use Illuminate\Config\Repository as Config;
use Laravel\Passport\PassportServiceProvider;
use Illuminate\Contracts\Foundation\Application as App;

class PassportServiceProviderTest extends TestCase
{
    public function test_can_use_crypto_keys_from_config()
    {
        $config = m::mock(Config::class, function ($config) {
            $config->shouldReceive('get')
                ->with('passport.private_key')
                ->andReturn('-----BEGIN RSA PRIVATE KEY-----\nconfig\n-----END RSA PRIVATE KEY-----');
        });

        $provider = new PassportServiceProvider(
            m::mock(App::class, ['make' => $config])
        );

        // Call protected makeCryptKey method
        $cryptKey = (function () {
            return $this->makeCryptKey('private');
        })->call($provider);

        $this->assertSame(
            "-----BEGIN RSA PRIVATE KEY-----\nconfig\n-----END RSA PRIVATE KEY-----",
            file_get_contents($cryptKey->getKeyPath())
        );
    }

    public function test_can_use_crypto_keys_from_local_disk()
    {
        Passport::loadKeysFrom(__DIR__.'/files');

        file_put_contents(
            __DIR__.'/files/oauth-private.key',
            "-----BEGIN RSA PRIVATE KEY-----\ndisk\n-----END RSA PRIVATE KEY-----"
        );

        $config = m::mock(Config::class, function ($config) {
            $config->shouldReceive('get')->with('passport.private_key')->andReturn(null);
        });

        $provider = new PassportServiceProvider(
            m::mock(App::class, ['make' => $config])
        );

        // Call protected makeCryptKey method
        $cryptKey = (function () {
            return $this->makeCryptKey('private');
        })->call($provider);

        $this->assertSame(
            "-----BEGIN RSA PRIVATE KEY-----\ndisk\n-----END RSA PRIVATE KEY-----",
            file_get_contents($cryptKey->getKeyPath())
        );

        @unlink(__DIR__.'/files/oauth-private.key');
    }
}
