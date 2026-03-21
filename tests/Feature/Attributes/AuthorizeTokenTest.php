<?php

namespace Laravel\Passport\Tests\Feature\Attributes;

use Illuminate\Support\Facades\Route;
use Laravel\Passport\Attributes\AuthorizeToken;
use Orchestra\Testbench\Attributes\RequiresLaravel;
use Orchestra\Testbench\TestCase;

#[RequiresLaravel('13.0.0')]
class AuthorizeTokenTest extends TestCase
{
    public function testItAppliesCheckTokenMiddleware(): void
    {
        $route = Route::get('/test', [AuthorizeTokenControllerTest::class, 'index']);

        $this->assertSame([
            'Laravel\Passport\Http\Middleware\CheckToken:all',
            'Laravel\Passport\Http\Middleware\CheckToken:only-index',
            'Laravel\Passport\Http\Middleware\CheckToken:also-index',
        ], $route->controllerMiddleware());

        $route = Route::get('/test', [AuthorizeTokenControllerTest::class, 'show']);

        $this->assertSame([
            'Laravel\Passport\Http\Middleware\CheckToken:all',
            'Laravel\Passport\Http\Middleware\CheckToken:except-index',
        ], $route->controllerMiddleware());
    }

    public function testItAppliesCheckTokenForAnyScopeMiddleware(): void
    {
        $route = Route::get('/test', [AuthorizeTokenControllerTest::class, 'store']);

        $this->assertSame([
            'Laravel\Passport\Http\Middleware\CheckToken:all',
            'Laravel\Passport\Http\Middleware\CheckToken:except-index',
            'Laravel\Passport\Http\Middleware\CheckTokenForAnyScope:only-store,something-else',
        ], $route->controllerMiddleware());
    }
}

#[AuthorizeToken('all')]
#[AuthorizeToken('only-index', only: ['index'])]
#[AuthorizeToken('except-index', except: ['index'])]
class AuthorizeTokenControllerTest
{
    #[AuthorizeToken('also-index')]
    public function index(): void
    {
    }

    public function show(): void
    {
    }

    #[AuthorizeToken(['only-store', 'something-else'], anyScope: true)]
    public function store(): void
    {
    }
}
