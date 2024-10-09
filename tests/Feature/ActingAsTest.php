<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckToken;
use Laravel\Passport\Http\Middleware\CheckTokenForAnyScope;
use Laravel\Passport\Passport;
use Workbench\App\Models\User;

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

        Passport::actingAs(new User());

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
        })->middleware(CheckToken::class.':admin,footest');

        Passport::actingAs(new User(), ['admin', 'footest']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testItCanGenerateDefinitionViaStaticMethod()
    {
        $signature = (string) CheckToken::using('admin');
        $this->assertSame('Laravel\Passport\Http\Middleware\CheckToken:admin', $signature);

        $signature = (string) CheckToken::using('admin', 'footest');
        $this->assertSame('Laravel\Passport\Http\Middleware\CheckToken:admin,footest', $signature);

        $signature = (string) CheckToken::using(['admin', 'footest']);
        $this->assertSame('Laravel\Passport\Http\Middleware\CheckToken:admin,footest', $signature);

        $signature = (string) CheckTokenForAnyScope::using('admin');
        $this->assertSame('Laravel\Passport\Http\Middleware\CheckTokenForAnyScope:admin', $signature);

        $signature = (string) CheckTokenForAnyScope::using('admin', 'footest');
        $this->assertSame('Laravel\Passport\Http\Middleware\CheckTokenForAnyScope:admin,footest', $signature);

        $signature = (string) CheckTokenForAnyScope::using(['admin', 'footest']);
        $this->assertSame('Laravel\Passport\Http\Middleware\CheckTokenForAnyScope:admin,footest', $signature);
    }

    public function testActingAsWhenTheRouteIsProtectedByCheckForAnyScopeMiddleware()
    {
        $this->withoutExceptionHandling();

        /** @var Registrar $router */
        $router = $this->app->make(Registrar::class);

        $router->get('/foo', function () {
            return 'bar';
        })->middleware(CheckTokenForAnyScope::class.':admin,footest');

        Passport::actingAs(new User(), ['footest']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsWhenTheRouteIsProtectedByCheckScopesMiddlewareWithInheritance()
    {
        Passport::$withInheritedScopes = true;

        $this->withoutExceptionHandling();

        Route::middleware(CheckToken::class.':foo:bar,baz:qux')->get('/foo', function () {
            return 'bar';
        });

        Passport::actingAs(new User(), ['foo', 'baz']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsWhenTheRouteIsProtectedByCheckForAnyScopeMiddlewareWithInheritance()
    {
        Passport::$withInheritedScopes = true;

        $this->withoutExceptionHandling();

        Route::middleware(CheckTokenForAnyScope::class.':foo:baz,baz:qux')->get('/foo', function () {
            return 'bar';
        });

        Passport::actingAs(new User(), ['foo']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }
}
