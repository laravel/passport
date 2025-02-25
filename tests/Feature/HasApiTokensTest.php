<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\Contracts\TokenAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class HasApiTokensTest extends PassportTestCase
{
    use WithLaravelMigrations;

    public function testGetProvider()
    {
        config([
            'auth.providers.admins' => ['driver' => 'eloquent', 'model' => AdminHasApiTokensStub::class],
            'auth.guards.api-admins' => ['driver' => 'passport', 'provider' => 'admins'],
            'auth.providers.customers' => ['driver' => 'eloquent', 'model' => CustomerHasApiTokensStub::class],
            'auth.guards.api-customers' => ['driver' => 'passport', 'provider' => 'customers'],
        ]);

        $this->assertSame('users', UserFactory::new()->create()->getProviderName());
        $this->assertSame('admins', (new AdminHasApiTokensStub)->getProviderName());
        $this->assertSame('customers', (new CustomerHasApiTokensStub)->getProviderName());
    }
}

class AdminHasApiTokensStub extends Authenticatable implements TokenAuthenticatable
{
    use HasApiTokens;
}

class CustomerHasApiTokensStub extends Authenticatable implements TokenAuthenticatable
{
    use HasApiTokens;
}
