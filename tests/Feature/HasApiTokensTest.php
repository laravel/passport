<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Foundation\Auth\User as Authenticatable;
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
            'auth.providers.customers' => ['driver' => 'database', 'table' => 'customer_has_api_tokens_stubs'],
            'auth.guards.api-customers' => ['driver' => 'passport', 'provider' => 'customers'],
        ]);

        $this->assertSame('users', UserFactory::new()->create()->getProvider());
        $this->assertSame('admins', (new AdminHasApiTokensStub)->getProvider());
        $this->assertSame('customers', (new CustomerHasApiTokensStub)->getProvider());
    }
}

class AdminHasApiTokensStub extends Authenticatable
{
    use HasApiTokens;
}

class CustomerHasApiTokensStub extends Authenticatable
{
    use HasApiTokens;
}
