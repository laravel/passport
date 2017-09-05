<?php

function storage_path($file = null)
{
    return __DIR__.DIRECTORY_SEPARATOR.$file;
}

function custom_path($file = null)
{
    return __DIR__.DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.$file;
}

class KeysCommandTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();

        @unlink(storage_path('oauth-private.key'));
        @unlink(storage_path('oauth-public.key'));
        @unlink(custom_path('oauth-private.key'));
        @unlink(custom_path('oauth-public.key'));
    }

    public function testPrivateAndPublicKeysAreGenerated()
    {
        $command = Mockery::mock(Laravel\Passport\Console\KeysCommand::class)
            ->makePartial()
            ->shouldReceive('info')
            ->with('Encryption keys generated successfully.')
            ->getMock();

        $rsa = new phpseclib\Crypt\RSA();

        $command->handle($rsa);

        $this->assertFileExists(storage_path('oauth-private.key'));
        $this->assertFileExists(storage_path('oauth-public.key'));
    }

    public function testPrivateAndPublicKeysAreGeneratedInCustomPath()
    {
        \Laravel\Passport\Passport::loadKeysFrom(custom_path());

        $command = Mockery::mock(Laravel\Passport\Console\KeysCommand::class)
            ->makePartial()
            ->shouldReceive('info')
            ->with('Encryption keys generated successfully.')
            ->getMock();

        $command->handle(new phpseclib\Crypt\RSA);

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

        $command->handle(new phpseclib\Crypt\RSA);
    }
}
