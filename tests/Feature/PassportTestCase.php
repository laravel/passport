<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class PassportTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withFactories(__DIR__.'/../../database/factories');

        $this->artisan('migrate:fresh');

        Passport::routes();

        $this->artisan('passport:keys');
    }

    protected function getEnvironmentSetUp($app)
    {
        $config = $app->make(Repository::class);

        $config->set('auth.defaults.provider', 'users');

        if (null !== ($userClass = $this->getUserClass())) {
            $config->set('auth.providers.users.model', $userClass);
        }

        $config->set('auth.guards.api', ['driver' => 'passport', 'provider' => 'users']);

        $config->set('database.default', 'forge');

        $config->set('database.connections.forge', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'username' => 'root',
            'password' => '',
            'database' => 'forge',
            'prefix' => '',
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
    protected function getUserClass(): ?string
    {
        return null;
    }
}
