<?php

namespace Laravel\Passport\Tests\Feature;

use Laravel\Passport\Client;
use Laravel\Passport\Http\Controllers\AuthorizationController;

class AuthorizeRequestTest extends PassportTestCase
{
    public function testActingAsWhenTheRouteIsProtectedByAuthMiddleware()
    {
        $redirectTo = '/auth/login';

        $this->app->when(AuthorizationController::class)
            ->needs('$loginUrl')
            ->give($redirectTo);

        $client = Client::factory()->create();

        $query = http_build_query([
            'client_id' => $client->id,
            'redirect_uri' => $client->redirect_uri,
            'response_type' => 'code',
            'scope' => '',
            'state' => $client->secret,
        ]);

        $url = "/oauth/authorize?{$query}";

        $response = $this->get($url);

        $response->assertStatus(302);
        $response->assertRedirect($redirectTo);
    }
}
