<?php

namespace Laravel\Passport\Tests;

use Illuminate\Container\Container;
use Laravel\Passport\Console\KeysCommand;
use Laravel\Passport\Passport;
use Mockery as m;
use phpseclib\Crypt\RSA;
use PHPUnit\Framework\TestCase;

function custom_path($file = null)
{
    return __DIR__.DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.$file;
}

function storage_path($file = null)
{
    return __DIR__.DIRECTORY_SEPARATOR.$file;
}

class KeysCommandTest extends TestCase
{
    protected function tearDown(): void
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

        Container::getInstance()->instance('path.storage', storage_path());

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
