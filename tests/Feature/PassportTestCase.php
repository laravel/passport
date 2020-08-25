<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class PassportTestCase extends TestCase
{
    use RefreshDatabase;

    const KEYS = __DIR__.'/keys';
    const PUBLIC_KEY = self::KEYS.'/oauth-public.key';
    const PRIVATE_KEY = self::KEYS.'/oauth-private.key';

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh');

        Passport::routes();

        @unlink(self::PUBLIC_KEY);
        @unlink(self::PRIVATE_KEY);

        $this->artisan('passport:keys');
    }

    protected function getEnvironmentSetUp($app)
    {
        $config = $app->make(Repository::class);

        $config->set('auth.defaults.provider', 'users');

        if (($userClass = $this->getUserClass()) !== null) {
            $config->set('auth.providers.users.model', $userClass);
        }

        $config->set('auth.guards.api', ['driver' => 'passport', 'provider' => 'users']);

        $app['config']->set('database.default', 'testbench');

        $app['config']->set('passport.storage.database.connection', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [PassportServiceProvider::class];
    }

    /**
     * Get the Eloquent user model class name.
     *
     * @return string|null
     */
    protected function getUserClass()
    {
    }
}
