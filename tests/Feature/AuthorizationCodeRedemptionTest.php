<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Workbench\Database\Factories\UserFactory;

class AuthorizationCodeRedemptionTest extends PassportTestCase
{
    use WithLaravelMigrations;

    public function testRejectsOverlappingRedemptionWithRefreshToken(): void
    {
        $this->assertSingleSuccessfulResponse(true);
    }

    public function testRejectsOverlappingRedemptionWithoutRefreshToken(): void
    {
        $this->assertSingleSuccessfulResponse(false);
    }

    private function assertSingleSuccessfulResponse(bool $refresh): void
    {
        $parameters = $this->authorizationCodeParameters($refresh);
        $armed = true;
        $winner = null;
        // Pause after the first revocation read has completed, then let a
        // second exchange finish before the first request resumes.
        DB::listen(function (QueryExecuted $query) use (&$armed, &$winner, $parameters) {
            if ($armed && str_starts_with(strtolower($query->sql), 'select') && str_contains($query->sql, 'oauth_auth_codes')) {
                $armed = false;
                $winner = $this->post('/oauth/token', $parameters);
            }
        });
        $this->post('/oauth/token', $parameters)->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');
        $this->assertNotNull($winner);
        $winner->assertOk()->assertJsonStructure(['access_token']);
        if ($refresh) {
            $winner->assertJsonStructure(['refresh_token']);
        } else {
            $this->assertArrayNotHasKey('refresh_token', $winner->json());
        }
        Route::get('/cas-resource', fn () => 'ok')->middleware('auth:api');
        $this->getJson('/cas-resource', ['Authorization' => 'Bearer '.$winner->json('access_token')])->assertOk();
        $this->assertTrue(Passport::authCode()->newQuery()->sole()->revoked);
        $this->post('/oauth/token', $parameters)->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');
    }

    private function authorizationCodeParameters(bool $refresh): array
    {
        Passport::authorizationView(fn ($parameters) => $parameters);
        $client = ClientFactory::new()->create();
        if (! $refresh) {
            $client->forceFill(['grant_types' => ['authorization_code']])->save();
        }
        $this->actingAs(UserFactory::new()->create(), 'web');
        $response = $this->get('/oauth/authorize?'.http_build_query([
            'client_id' => $client->getKey(),
            'redirect_uri' => $client->redirect_uris[0],
            'response_type' => 'code',
            'scope' => '',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', str_repeat('v', 64), true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ]))->assertOk();
        $approved = $this->post('/oauth/authorize', [
            'auth_token' => $response->json('authToken'),
        ])->assertRedirect();
        parse_str(parse_url($approved->headers->get('Location'), PHP_URL_QUERY), $query);

        return [
            'grant_type' => 'authorization_code',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'redirect_uri' => $client->redirect_uris[0],
            'code' => $query['code'],
            'code_verifier' => str_repeat('v', 64),
        ];
    }
}
