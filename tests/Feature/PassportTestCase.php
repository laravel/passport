<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use phpseclib3\Crypt\RSA as LegacyRSA;
use phpseclib4\Crypt\RSA;
use Workbench\App\Models\User;

use function Orchestra\Testbench\artisan;

abstract class PassportTestCase extends TestCase
{
    use VerifiesDoubles;

    use LazilyRefreshDatabase, WithWorkbench;

    const KEYS = __DIR__.'/../keys';
    const PUBLIC_KEY = self::KEYS.'/oauth-public.key';
    const PRIVATE_KEY = self::KEYS.'/oauth-private.key';

    protected function setUp(): void
    {
        $rsa = class_exists(LegacyRSA::class) ? LegacyRSA::class : RSA::class;

        $this->afterApplicationCreated(function () use ($rsa) {
            Passport::loadKeysFrom(self::KEYS);

            @unlink(self::PUBLIC_KEY);
            @unlink(self::PRIVATE_KEY);

            $rsa::forceEngine('PHP');

            artisan($this, 'passport:keys');
        });

        $this->beforeApplicationDestroyed(function () use ($rsa) {
            @unlink(self::PUBLIC_KEY);
            @unlink(self::PRIVATE_KEY);

            $rsa::forceEngine(null);
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
