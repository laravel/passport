<?php

namespace Laravel\Passport\Tests\Feature;

use Laravel\Passport\Client;
use Laravel\Passport\Database\Factories\ClientFactory;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;

class DeviceCodeControllerTest extends PassportTestCase
{
    use WithLaravelMigrations;

    public function testIssuingDeviceCode()
    {
        $this->withoutExceptionHandling();

        /** @var Client $client */
        $client = ClientFactory::new()->create();

        $response = $this->post(
            '/oauth/device/code',
            [
                'client_id' => $client->getKey(),
                'scope' => '',
            ]
        );

        $response->assertOk();

        $response = $response->json();

        $this->assertArrayHasKey('device_code', $response);
        $this->assertArrayHasKey('user_code', $response);
        $this->assertArrayHasKey('expires_in', $response);
        // $this->assertArrayHasKey('interval', $response); // TODO https://github.com/thephpleague/oauth2-server/pull/1410
        $this->assertSame('http://localhost/oauth/device', $response['verification_uri']);
        $this->assertStringStartsWith('http://localhost/oauth/device?user_code=', $response['verification_uri_complete']);
    }
}
