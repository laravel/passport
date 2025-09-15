<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Http\Request;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\PersonalAccessTokenResult;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class PersonalAccessGrantWithTrustedHostsTest extends PassportTestCase
{
    use WithLaravelMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('app.url', 'https://laravel.test');

        Request::setTrustedHosts(['laravel.test']);
    }

    protected function tearDown(): void
    {
        $this->app['config']->set('app.url', 'http://localhost');

        Request::setTrustedHosts([]);

        parent::tearDown();
    }

    public function testIssueToken()
    {
        $user = UserFactory::new()->create();

        ClientFactory::new()->asPersonalAccessTokenClient()->create();

        $result = $user->createToken('test');

        $this->assertInstanceOf(PersonalAccessTokenResult::class, $result);
        $this->assertArrayHasKey('accessToken', $result->toArray());
    }
}
