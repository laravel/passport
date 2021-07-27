<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application as App;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportServiceProvider;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class PassportServiceProviderTest extends TestCase
{
    public function test_can_use_crypto_keys_from_config()
    {
        $privateKey = openssl_pkey_new();

        openssl_pkey_export($privateKey, $privateKeyString);

        $config = m::mock(Config::class, function ($config) use ($privateKeyString) {
            $config->shouldReceive('get')
                ->with('passport.private_key')
                ->andReturn($privateKeyString);
        });

        $provider = new PassportServiceProvider(
            m::mock(App::class, ['make' => $config])
        );

        // Call protected makeCryptKey method
        $cryptKey = (function () {
            return $this->makeCryptKey('private');
        })->call($provider);

        $this->assertSame(
            $privateKeyString,
            $cryptKey->getKeyContents()
        );
    }

    public function test_can_use_crypto_keys_from_local_disk()
    {
        Passport::loadKeysFrom(__DIR__.'/../keys');

        $privateKey = openssl_pkey_new();

        openssl_pkey_export_to_file($privateKey, __DIR__.'/../keys/oauth-private.key');
        openssl_pkey_export($privateKey, $privateKeyString);

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
            $privateKeyString,
            file_get_contents($cryptKey->getKeyPath())
        );

        @unlink(__DIR__.'/../keys/oauth-private.key');
    }
}
