<?php

namespace Laravel\Passport\Tests\Unit;

use Defuse\Crypto\Key;
use Illuminate\Encryption\Encrypter;
use Laravel\Passport\Passport;
use PHPUnit\Framework\TestCase;

class AuthorizationServerEncryptionKeyTest extends TestCase
{
    protected function tearDown(): void
    {
        Passport::encryptAuthorizationServerTokensUsing(null);
        Passport::encryptTokensUsing(null);

        parent::tearDown();
    }

    public function test_it_defaults_to_the_token_encryption_key()
    {
        $encrypter = new Encrypter(str_repeat('a', 16));

        $this->assertSame(
            Passport::tokenEncryptionKey($encrypter),
            Passport::authorizationServerEncryptionKey($encrypter)
        );
    }

    public function test_it_defaults_to_the_token_encryption_key_callback()
    {
        $encrypter = new Encrypter(str_repeat('a', 16));

        Passport::encryptTokensUsing(fn (Encrypter $encrypter) => $encrypter->getKey().'-tenant');

        $this->assertSame(
            $encrypter->getKey().'-tenant',
            Passport::authorizationServerEncryptionKey($encrypter)
        );
    }

    public function test_it_may_resolve_a_string()
    {
        $encrypter = new Encrypter(str_repeat('a', 16));

        Passport::encryptAuthorizationServerTokensUsing(fn () => 'a-string-key');

        $this->assertSame('a-string-key', Passport::authorizationServerEncryptionKey($encrypter));
    }

    public function test_it_may_resolve_a_defuse_key()
    {
        $encrypter = new Encrypter(str_repeat('a', 16));
        $key = Key::createNewRandomKey();

        Passport::encryptAuthorizationServerTokensUsing(fn () => $key);

        $this->assertSame($key, Passport::authorizationServerEncryptionKey($encrypter));
    }

    public function test_it_does_not_affect_the_token_encryption_key()
    {
        $encrypter = new Encrypter(str_repeat('a', 16));

        Passport::encryptAuthorizationServerTokensUsing(fn () => Key::createNewRandomKey());

        $this->assertSame($encrypter->getKey(), Passport::tokenEncryptionKey($encrypter));
    }
}
