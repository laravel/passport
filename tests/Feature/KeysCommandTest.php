<?php

namespace Laravel\Passport\Tests\Feature;

class KeysCommandTest extends PassportTestCase
{
    public function testPrivateAndPublicKeysAreGenerated()
    {
        $this->assertFileExists(self::PUBLIC_KEY);
        $this->assertFileExists(self::PRIVATE_KEY);

        $privateKey = openssl_pkey_get_private(file_get_contents(self::PRIVATE_KEY));
        $publicKey = openssl_pkey_get_public(file_get_contents(self::PUBLIC_KEY));

        $this->assertNotFalse($privateKey);
        $this->assertNotFalse($publicKey);
        $this->assertTrue(openssl_sign('passport', $signature, $privateKey, OPENSSL_ALGO_SHA256));
        $this->assertSame(1, openssl_verify('passport', $signature, $publicKey, OPENSSL_ALGO_SHA256));
    }

    public function testPrivateAndPublicKeysShouldNotBeGeneratedTwice()
    {
        $this->artisan('passport:keys')
            ->assertFailed()
            ->expectsOutputToContain('Encryption keys already exist. Use the --force option to overwrite them.');
    }
}
