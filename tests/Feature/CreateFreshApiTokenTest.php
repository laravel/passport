<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CreateFreshApiToken;
use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Symfony\Component\HttpFoundation\Cookie;
use Workbench\App\Models\User;
use Workbench\Database\Factories\UserFactory;

class CreateFreshApiTokenTest extends PassportTestCase
{
    use WithLaravelMigrations;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::new()->create();

        Route::middleware(['web', CreateFreshApiToken::class])->group(function () {
            Route::get('/fresh', fn () => 'response');
            Route::post('/fresh', fn () => 'response');
            Route::get('/redirect', fn () => redirect('/fresh'));
            Route::get('/has-token', fn () => response('response')->withCookie(new Cookie(Passport::cookie(), 'existing')));
        });

        Route::get('/fresh-api', fn () => 'response')->middleware(['web', CreateFreshApiToken::using('api')]);
        Route::get('/user', fn (Request $request) => $request->user()->getKey())->middleware('auth:api');
    }

    public function test_should_receive_a_fresh_token()
    {
        $response = $this->actingAs($this->user)->get('/fresh');

        $response->assertOk()->assertCookie(Passport::cookie());

        $this->withCredentials()
            ->withCookie(Passport::cookie(), $response->getCookie(Passport::cookie())->getValue())
            ->withHeader('X-CSRF-TOKEN', session()->token())
            ->getJson('/user')
            ->assertOk()
            ->assertSee($this->user->getKey());
    }

    public function test_should_not_receive_a_fresh_token_for_other_http_verbs()
    {
        $this->actingAs($this->user)->post('/fresh')->assertOk()->assertCookieMissing(Passport::cookie());
    }

    public function test_should_not_receive_a_fresh_token_for_guests()
    {
        $this->get('/fresh')->assertOk()->assertCookieMissing(Passport::cookie());
    }

    public function test_should_not_receive_a_fresh_token_for_redirects()
    {
        $this->actingAs($this->user)->get('/redirect')->assertRedirect()->assertCookieMissing(Passport::cookie());
    }

    public function test_should_not_receive_a_fresh_token_for_response_that_already_has_token()
    {
        $this->actingAs($this->user)
            ->get('/has-token')
            ->assertOk()
            ->assertCookie(Passport::cookie(), 'existing');
    }

    public function test_should_resolve_the_user_from_the_given_guard()
    {
        $this->actingAs($this->user, 'web')->get('/fresh-api')->assertOk()->assertCookieMissing(Passport::cookie());
    }
}
