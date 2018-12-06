<?php

namespace Laravel\Passport\Tests;

use Mockery as m;
use phpseclib\Crypt\RSA;
use Laravel\Passport\Passport;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\Console\KeysCommand;

function custom_path($file = null)
{
    return __DIR__.DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.$file;
}

class KeysCommandTest extends TestCase
{
    public function tearDown()
    {
        m::close();

        @unlink(storage_path('oauth-private.key'));
        @unlink(storage_path('oauth-public.key'));
        @unlink(custom_path('oauth-private.key'));
        @unlink(custom_path('oauth-public.key'));
    }

    public function testPrivateAndPublicKeysAreGenerated()
    {
        $command = m::mock(KeysCommand::class)
            ->makePartial()
            ->shouldReceive('info')
            ->with('Encryption keys generated successfully.')
            ->getMock();

        $rsa = new RSA();

        $command->handle($rsa);

        $this->assertFileExists(storage_path('oauth-private.key'));
        $this->assertFileExists(storage_path('oauth-public.key'));
    }

    public function testPrivateAndPublicKeysAreGeneratedInCustomPath()
    {
        Passport::loadKeysFrom(custom_path());

        $command = m::mock(KeysCommand::class)
            ->makePartial()
            ->shouldReceive('info')
            ->with('Encryption keys generated successfully.')
            ->getMock();

        $command->handle(new RSA);

        $this->assertFileExists(custom_path('oauth-private.key'));
        $this->assertFileExists(custom_path('oauth-public.key'));

        return $command;
    }

    /**
     * @depends testPrivateAndPublicKeysAreGeneratedInCustomPath
     */
    public function testPrivateAndPublicKeysShouldNotBeGeneratedTwice($command)
    {
        $command->shouldReceive('option')
            ->with('force')
            ->andReturn(false);

        $command->shouldReceive('error')
            ->with('Encryption keys already exist. Use the --force option to overwrite them.');

        $command->handle(new RSA);
    }
}
