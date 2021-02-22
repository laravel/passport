<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Container\Container;
use Laravel\Passport\Console\KeysCommand;
use Laravel\Passport\Passport;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class KeysCommandTest extends TestCase
{
    const KEYS = __DIR__.'/../keys';
    const PUBLIC_KEY = self::KEYS.'/oauth-public.key';
    const PRIVATE_KEY = self::KEYS.'/oauth-private.key';

    protected function setUp(): void
    {
        parent::setUp();

        @unlink(self::PUBLIC_KEY);
        @unlink(self::PRIVATE_KEY);
    }

    protected function tearDown(): void
    {
        m::close();

        @unlink(self::PUBLIC_KEY);
        @unlink(self::PRIVATE_KEY);
    }

    public function testPrivateAndPublicKeysAreGenerated()
    {
        $command = m::mock(KeysCommand::class)
            ->makePartial()
            ->shouldReceive('info')
            ->with('Encryption keys generated successfully.')
            ->getMock();

        Container::getInstance()->instance('path.storage', self::KEYS);

        $command->handle();

        $this->assertFileExists(self::PUBLIC_KEY);
        $this->assertFileExists(self::PRIVATE_KEY);
    }

    public function testPrivateAndPublicKeysAreGeneratedInCustomPath()
    {
        Passport::loadKeysFrom(self::KEYS);

        $command = m::mock(KeysCommand::class)
            ->makePartial()
            ->shouldReceive('info')
            ->with('Encryption keys generated successfully.')
            ->getMock();

        $command->handle();

        $this->assertFileExists(self::PUBLIC_KEY);
        $this->assertFileExists(self::PRIVATE_KEY);

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

        $command->handle();
    }
}
