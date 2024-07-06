<?php

namespace Laravel\Passport\Tests\Feature;

use Laravel\Passport\Database\Factories\ClientFactory;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;

class DeviceCodeControllerTest extends PassportTestCase
{
    use WithLaravelMigrations;

    public function testIssuingDeviceCode()
    {
        $client = ClientFactory::new()->create();

        $response = $this->post('/oauth/device/code', [
            'client_id' => $client->getKey(),
            'scope' => '',
        ]);

        $response->assertOk();
        $json = $response->json();

        $this->assertArrayHasKey('device_code', $json);
        $this->assertArrayHasKey('user_code', $json);
        $this->assertArrayHasKey('expires_in', $json);
        // $this->assertArrayHasKey('interval', $json); // TODO https://github.com/thephpleague/oauth2-server/pull/1410
        $this->assertSame('http://localhost/oauth/device', $json['verification_uri']);
        $this->assertStringStartsWith('http://localhost/oauth/device?user_code=', $json['verification_uri_complete']);
    }
}
