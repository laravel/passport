<?php

namespace Laravel\Passport\Tests\Feature;

use Mockery as m;

class KeysCommandTest extends PassportTestCase
{
    protected function tearDown(): void
    {
        m::close();

        @unlink(self::PUBLIC_KEY);
        @unlink(self::PRIVATE_KEY);
    }

    public function testPrivateAndPublicKeysAreGenerated()
    {
        $this->assertFileExists(self::PUBLIC_KEY);
        $this->assertFileExists(self::PRIVATE_KEY);
    }

    public function testPrivateAndPublicKeysShouldNotBeGeneratedTwice()
    {
        $this->artisan('passport:keys')
            ->assertFailed()
            ->expectsOutput('Encryption keys already exist. Use the --force option to overwrite them.');
    }
}
