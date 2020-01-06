<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Contracts\Config\Repository;
use Laravel\Passport\PassportServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class PassportTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('passport:keys');
    }

    protected function getEnvironmentSetUp($app)
    {
        $app->make(Repository::class)->set('auth.guards.api', ['driver' => 'passport', 'provider' => 'users']);
    }

    protected function getPackageProviders($app)
    {
        return [PassportServiceProvider::class];
    }
}
