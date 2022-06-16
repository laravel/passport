<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Http\Middleware\CheckForAnyScope;
use Laravel\Passport\Http\Middleware\CheckScopes;
use Laravel\Passport\Passport;

class ActingAsTest extends PassportTestCase
{
    public function testActingAsWhenTheRouteIsProtectedByAuthMiddleware()
    {
        $this->withoutExceptionHandling();

        /** @var Registrar $router */
        $router = $this->app->make(Registrar::class);

        $router->get('/foo', function () {
            return 'bar';
        })->middleware('auth:api');

        Passport::actingAs(new PassportUser());

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsWhenTheRouteIsProtectedByCheckScopesMiddleware()
    {
        $this->withoutExceptionHandling();

        /** @var Registrar $router */
        $router = $this->app->make(Registrar::class);

        $router->get('/foo', function () {
            return 'bar';
        })->middleware(CheckScopes::class.':admin,footest');

        Passport::actingAs(new PassportUser(), ['admin', 'footest']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsWhenTheRouteIsProtectedByCheckForAnyScopeMiddleware()
    {
        $this->withoutExceptionHandling();

        /** @var Registrar $router */
        $router = $this->app->make(Registrar::class);

        $router->get('/foo', function () {
            return 'bar';
        })->middleware(CheckForAnyScope::class.':admin,footest');

        Passport::actingAs(new PassportUser(), ['footest']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsWhenTheRouteIsProtectedByCheckScopesMiddlewareWithInheritance()
    {
        Passport::$withInheritedScopes = true;

        $this->withoutExceptionHandling();

        Route::middleware(CheckScopes::class.':foo:bar,baz:qux')->get('/foo', function () {
            return 'bar';
        });

        Passport::actingAs(new PassportUser(), ['foo', 'baz']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsWhenTheRouteIsProtectedByCheckForAnyScopeMiddlewareWithInheritance()
    {
        Passport::$withInheritedScopes = true;

        $this->withoutExceptionHandling();

        Route::middleware(CheckForAnyScope::class.':foo:baz,baz:qux')->get('/foo', function () {
            return 'bar';
        });

        Passport::actingAs(new PassportUser(), ['foo']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }
}

class PassportUser extends User
{
    use HasApiTokens;

    protected $table = 'users';
}
