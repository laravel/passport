<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\User;

abstract class PassportTestCase extends TestCase
{
    use LazilyRefreshDatabase, WithWorkbench;

    const KEYS = __DIR__.'/../keys';
    const PUBLIC_KEY = self::KEYS.'/oauth-public.key';
    const PRIVATE_KEY = self::KEYS.'/oauth-private.key';

    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            Passport::loadKeysFrom(self::KEYS);

            @unlink(self::PUBLIC_KEY);
            @unlink(self::PRIVATE_KEY);

            $this->artisan('passport:keys');
        });

        $this->beforeApplicationDestroyed(function () {
            @unlink(self::PUBLIC_KEY);
            @unlink(self::PRIVATE_KEY);
        });

        parent::setUp();
    }

    protected function defineEnvironment($app)
    {
        $config = $app->make(Repository::class);

        $config->set([
            'auth.defaults.provider' => 'users',
            'auth.providers.users.model' => User::class,
            'auth.guards.api' => ['driver' => 'passport', 'provider' => 'users'],
            'database.default' => 'testing',
        ]);
    }
}
