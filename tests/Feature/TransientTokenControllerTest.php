<?php

namespace Laravel\Passport\Tests\Feature;

use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class TransientTokenControllerTest extends PassportTestCase
{
    use WithLaravelMigrations;

    public function test_token_can_be_refreshed()
    {
        $this->actingAs(UserFactory::new()->create())
            ->post('/oauth/token/refresh')
            ->assertOk()
            ->assertSee('Refreshed.')
            ->assertCookie(Passport::cookie());
    }
}
