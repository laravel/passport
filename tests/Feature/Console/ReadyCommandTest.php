<?php

namespace Laravel\Passport\Tests\Feature\Console;

use Laravel\Passport\Tests\Feature\PassportTestCase;
use Mockery as m;

class ReadyCommandTest extends PassportTestCase
{
    protected function tearDown(): void
    {
        m::close();

        @unlink(self::PUBLIC_KEY);
        @unlink(self::PRIVATE_KEY);
    }

    public function testNotReadyWithoutClients()
    {
        $this->artisan('passport:ready')
            ->assertFailed()
            ->expectsOutput('Passport clients are missing. Please run "php artisan passport:install" to generate them');
    }

    public function testNotReadyWithoutKeys()
    {
        @unlink(self::PUBLIC_KEY);
        @unlink(self::PRIVATE_KEY);

        $this->artisan('passport:ready')
            ->assertFailed()
            ->expectsOutput('Passport keys are missing. Please run "php artisan passport:install" to generate them.');
    }

    public function testReadyAfterInstall()
    {
        $this->artisan('passport:install');

        $this->artisan('passport:ready')
            ->assertSuccessful();
    }
}
