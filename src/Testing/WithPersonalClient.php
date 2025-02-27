<?php

namespace App\Traits;

use Laravel\Passport\Client;
use Laravel\Passport\PersonalAccessClient;

trait WithPersonalClient
{
    public function setUp(): void
    {

        $client = Client::factory()->create([
            'id' => env("PASSPORT_PERSONAL_ACCESS_CLIENT_ID"),
            'secret' => env("PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET"),
            'personal_access_client' => true
        ]);

        PersonalAccessClient::create([
            'client_id' => $client->id,
        ]);
    }
}
