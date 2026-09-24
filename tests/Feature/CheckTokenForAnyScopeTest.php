<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\Http\Middleware\CheckTokenForAnyScope;
use Laravel\Passport\Passport;
use Laravel\Passport\TransientToken;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\App\Models\User;
use Workbench\Database\Factories\UserFactory;

class CheckTokenForAnyScopeTest extends PassportTestCase
{
    use WithLaravelMigrations;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Passport::tokensCan([
            'foo' => 'Foo',
            'bar' => 'Bar',
            'baz' => 'Baz',
        ]);

        ClientFactory::new()->asPersonalAccessTokenClient()->create();

        $this->user = UserFactory::new()->create();

        Route::get('/bearer', fn () => 'response')->middleware(CheckTokenForAnyScope::using('foo', 'bar'));
        Route::get('/guarded', fn () => 'response')->middleware(['auth:api', CheckTokenForAnyScope::using('foo', 'bar')]);
    }

    public function test_request_is_passed_along_if_token_has_any_required_scopes()
    {
        $token = $this->user->createToken('test', ['bar'])->accessToken;

        $this->withToken($token)->getJson('/bearer')->assertOk()->assertSee('response');
    }

    public function test_request_is_passed_along_if_token_has_all_scopes()
    {
        $token = $this->user->createToken('test', ['*'])->accessToken;

        $this->withToken($token)->getJson('/bearer')->assertOk();
    }

    public function test_request_is_forbidden_if_token_has_none_of_the_required_scopes()
    {
        $token = $this->user->createToken('test', ['baz'])->accessToken;

        $this->withToken($token)->getJson('/bearer')->assertForbidden();
    }

    public function test_request_is_unauthorized_if_token_is_invalid()
    {
        $this->withToken('invalid')->getJson('/bearer')->assertUnauthorized();
    }

    public function test_request_is_unauthorized_if_token_is_revoked()
    {
        $result = $this->user->createToken('test', ['*']);
        $result->getToken()->revoke();

        $this->withToken($result->accessToken)->getJson('/bearer')->assertUnauthorized();
    }

    public function test_authenticated_user_token_is_checked_for_scopes()
    {
        $token = $this->user->createToken('test', ['bar'])->accessToken;

        $this->withToken($token)->getJson('/guarded')->assertOk();
    }

    public function test_authenticated_user_token_is_forbidden_without_scopes()
    {
        $token = $this->user->createToken('test', ['baz'])->accessToken;

        $this->withToken($token)->getJson('/guarded')->assertForbidden();
    }

    public function test_request_is_passed_along_if_token_is_transient()
    {
        $this->actingAs($this->user->withAccessToken(new TransientToken), 'api')
            ->getJson('/guarded')
            ->assertOk();
    }
}
