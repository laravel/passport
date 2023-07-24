<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Contracts\Routing\Registrar;
use Laravel\Passport\Client;
use Laravel\Passport\Http\Middleware\CheckClientCredentials;
use Laravel\Passport\Http\Middleware\CheckClientCredentialsForAnyScope;
use Laravel\Passport\Passport;

class ActingAsClientTest extends PassportTestCase
{
    protected function tearDown(): void
    {
        Passport::setDefaultScope([]);
        parent::tearDown();
    }

    public function testActingAsClientWhenTheRouteIsProtectedByCheckClientCredentialsMiddleware()
    {
        $this->withoutExceptionHandling();

        /** @var Registrar $router */
        $router = $this->app->make(Registrar::class);

        $router->get('/foo', function () {
            return 'bar';
        })->middleware(CheckClientCredentials::class);

        Passport::actingAsClient(new Client());

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsClientWhenTheRouteIsProtectedByCheckClientCredentialsForAnyScope()
    {
        $this->withoutExceptionHandling();

        /** @var Registrar $router */
        $router = $this->app->make(Registrar::class);

        $router->get('/foo', function () {
            return 'bar';
        })->middleware(CheckClientCredentialsForAnyScope::class.':testFoo');

        Passport::actingAsClient(new Client(), ['testFoo']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsClientWhenTheRouteIsProtectedByCheckClientCredentialsMiddlewareWithDefaultScope()
    {
        $this->withoutExceptionHandling();

        /** @var Registrar $router */
        $router = $this->app->make(Registrar::class);

        Passport::setDefaultScope([
            'foo' => 'It requests foo access',
            'bar' => 'it requests bar access',
        ]);

        $router->get('/foo', function () {
            return 'bar';
        })->middleware(CheckClientCredentials::class);

        Passport::actingAsClient(new Client(), Passport::defaultScopes()->pluck('id')->toArray());

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsClientWhenTheRouteIsProtectedByCheckClientCredentialsForAnyScopeWithDefaultScope()
    {
        $this->withoutExceptionHandling();

        /** @var Registrar $router */
        $router = $this->app->make(Registrar::class);

        Passport::setDefaultScope([
            'foo' => 'It requests foo access',
            'bar' => 'it requests bar access',
        ]);

        $defaultScopes = Passport::defaultScopes()->pluck('id')->values()->toArray();
        $middlewareScopes = implode(',', $defaultScopes);

        $router->get('/foo', function () {
            return 'bar';
        })->middleware(CheckClientCredentialsForAnyScope::class.':'.$middlewareScopes);

        Passport::actingAsClient(new Client(), $defaultScopes);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsClientSetsTheClientOnTheGuard()
    {
        Passport::actingAsClient($client = new Client());

        $this->assertSame($client, app('auth')->client());
    }
}
