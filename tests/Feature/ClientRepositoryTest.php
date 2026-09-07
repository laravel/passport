<?php

namespace Laravel\Passport\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\ClientRepository;

class ClientRepositoryTest extends PassportTestCase
{
    public function testClientsCanBeCreatedWithMetadataUris(): void
    {
        $repository = new ClientRepository;
        $logoUri = 'https://client.example.com/logo?version='.str_repeat('a', 300);
        $clientUri = 'https://client.example.com/about?version='.str_repeat('b', 300);
        $redirectUris = ['https://client.example.com/callback'];

        foreach ([
            $repository->createAuthorizationCodeGrantClient('Client', $redirectUris, logoUri: $logoUri, clientUri: $clientUri),
            $repository->createImplicitGrantClient('Client', $redirectUris, logoUri: $logoUri, clientUri: $clientUri),
            $repository->createDeviceAuthorizationGrantClient('Client', logoUri: $logoUri, clientUri: $clientUri),
        ] as $client) {
            $client->refresh();
            $this->assertSame($logoUri, $client->logo_uri);
            $this->assertSame($clientUri, $client->client_uri);
        }
    }

    public function testClientsCanBeCreatedWithoutMetadataColumns(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->dropColumn(['logo_uri', 'client_uri']);
        });

        $repository = new ClientRepository;

        foreach ([null, 'https://client.example.com/logo.png'] as $logoUri) {
            $client = $repository->createAuthorizationCodeGrantClient(
                'Client', ['https://client.example.com/callback'], logoUri: $logoUri, clientUri: 'https://client.example.com'
            );

            $this->assertArrayNotHasKey('logo_uri', $client->getAttributes());
            $this->assertArrayNotHasKey('logo_uri', $client->fresh()->getAttributes());
            $this->assertArrayNotHasKey('client_uri', $client->getAttributes());
            $this->assertArrayNotHasKey('client_uri', $client->fresh()->getAttributes());
        }
    }
}
