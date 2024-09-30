<?php

namespace Laravel\Passport\Tests\Feature;

use Laravel\Passport\Passport;
use Laravel\Passport\PassportServiceProvider;

class PassportServiceProviderTest extends PassportTestCase
{
    protected function tearDown(): void
    {
        @unlink(__DIR__.'/../keys/oauth-private.key');
    }

    public function test_can_use_crypto_keys_from_config()
    {
        $privateKey = openssl_pkey_new();

        openssl_pkey_export($privateKey, $privateKeyString);

        config(['passport.private_key' => $privateKeyString]);

        $provider = new PassportServiceProvider($this->app);

        // Call protected makeCryptKey method
        $cryptKey = (fn () => $this->makeCryptKey('private'))->call($provider);

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
        chmod(__DIR__.'/../keys/oauth-private.key', 0600);

        config(['passport.private_key' => null]);

        $provider = new PassportServiceProvider($this->app);

        // Call protected makeCryptKey method
        $cryptKey = (fn () => $this->makeCryptKey('private'))->call($provider);

        $this->assertSame(
            $privateKeyString,
            file_get_contents($cryptKey->getKeyPath())
        );
    }
}
