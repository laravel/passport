<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\Http\Middleware\EnsureClientIsResourceOwner;
use Laravel\Passport\Http\Middleware\CheckToken;
use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;

class ClientCredentialsGrantTest extends PassportTestCase
{
    use WithLaravelMigrations;

    protected function setUp(): void
    {
        PassportTestCase::setUp();

        Passport::tokensCan([
            'create' => 'Create',
            'read' => 'Read',
            'update' => 'Update',
            'delete' => 'Delete',
        ]);
    }

    public function testIssueAccessToken()
    {
        $client = ClientFactory::new()->asClientCredentials()->create();

        $json = $this->post('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
            'scope' => 'create read delete',
        ])->assertOk()->json();

        $this->assertArrayHasKey('access_token', $json);
        $this->assertSame('Bearer', $json['token_type']);
        $this->assertSame(31536000, $json['expires_in']);

        Route::get('/foo', fn (Request $request) => response('response'))
            ->middleware([EnsureClientIsResourceOwner::class, CheckToken::using(['create', 'delete'])]);

        $response = $this->withToken($json['access_token'], $json['token_type'])->get('/foo');
        $response->assertOk();
        $this->assertSame('response', $response->content());

        Route::get('/bar', fn (Request $request) => response('response'))
            ->middleware(CheckToken::using(['create', 'update']));

        $response = $this->withToken($json['access_token'], $json['token_type'])->get('/bar');
        $response->assertForbidden();
    }
}
