<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckForAnyScope;
use Laravel\Passport\Http\Middleware\CheckScopes;
use Laravel\Passport\Passport;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
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
        })->middleware(CheckScopes::class.':admin,footest');

        Passport::actingAs(new User(), ['admin', 'footest']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testItCanGenerateDefinitionViaStaticMethod()
    {
        $signature = (string) CheckScopes::using('admin');
        $this->assertSame('Laravel\Passport\Http\Middleware\CheckScopes:admin', $signature);

        $signature = (string) CheckScopes::using('admin', 'footest');
        $this->assertSame('Laravel\Passport\Http\Middleware\CheckScopes:admin,footest', $signature);

        $signature = (string) CheckForAnyScope::using('admin');
        $this->assertSame('Laravel\Passport\Http\Middleware\CheckForAnyScope:admin', $signature);

        $signature = (string) CheckForAnyScope::using('admin', 'footest');
        $this->assertSame('Laravel\Passport\Http\Middleware\CheckForAnyScope:admin,footest', $signature);
    }

    public function testActingAsWhenTheRouteIsProtectedByCheckForAnyScopeMiddleware()
    {
        $this->withoutExceptionHandling();

        /** @var Registrar $router */
        $router = $this->app->make(Registrar::class);

        $router->get('/foo', function () {
            return 'bar';
        })->middleware(CheckForAnyScope::class.':admin,footest');

        Passport::actingAs(new User(), ['footest']);

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

        Passport::actingAs(new User(), ['foo', 'baz']);

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

        Passport::actingAs(new User(), ['foo']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsCreatesAccessTokenAndRefreshToken()
    {
        Passport::actingAs(new User(), ['foo']);

        $this->assertInstanceOf(User::class, auth()->user());
        $this->assertInstanceOf(Token::class, auth()->user()->token());
        $this->assertTrue(auth()->user()->token()->exists);
        $this->assertSame(['foo'], auth()->user()->token()->scopes);
        $this->assertInstanceOf(RefreshToken::class, $refreshToken = RefreshToken::firstWhere('access_token_id', auth()->user()->token()->id));
        $this->assertTrue($refreshToken->exists);
    }
}
