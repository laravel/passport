<?php

namespace Laravel\Passport\Tests\Feature;

use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class ScopeControllerTest extends PassportTestCase
{
    use WithLaravelMigrations;

    protected function tearDown(): void
    {
        Passport::$registersJsonApiRoutes = false;

        parent::tearDown();
    }

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        Passport::$registersJsonApiRoutes = true;
    }

    public function test_scopes_can_be_retrieved()
    {
        Passport::tokensCan([
            'place-orders' => 'Place orders',
            'check-status' => 'Check order status',
        ]);

        $this->actingAs(UserFactory::new()->create())
            ->getJson('/oauth/scopes')
            ->assertOk()
            ->assertExactJson([
                ['id' => 'place-orders', 'description' => 'Place orders'],
                ['id' => 'check-status', 'description' => 'Check order status'],
            ]);
    }
}
