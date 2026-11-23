<?php

namespace Laravel\Passport\Tests\Feature;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;

class JsonWebKeySetControllerTest extends PassportTestCase
{
    use WithLaravelMigrations;

    public function test_public_key_is_published_as_json_web_key_set()
    {
        $rsa = openssl_pkey_get_details(openssl_pkey_get_public(file_get_contents(self::PUBLIC_KEY)))['rsa'];
        $n = rtrim(strtr(base64_encode($rsa['n']), '+/', '-_'), '=');

        $this->getJson('/oauth/jwks')
            ->assertOk()
            ->assertExactJson(['keys' => [[
                'kty' => 'RSA',
                'n' => $n,
                'e' => 'AQAB',
                'alg' => 'RS256',
                'use' => 'sig',
                'kid' => rtrim(strtr(base64_encode(hash('sha256', '{"e":"AQAB","kty":"RSA","n":"'.$n.'"}', true)), '+/', '-_'), '='),
            ]]]);
    }

    public function test_key_id_is_rfc_7638_thumbprint()
    {
        // The example key and thumbprint from RFC 7638 section 3.1.
        $key = JWK::parseKey([
            'kty' => 'RSA',
            'n' => '0vx7agoebGcQSuuPiLJXZptN9nndrQmbXEps2aiAFbWhM78LhWx4cbbfAAtVT86zwu1RK7aPFFxuhDR1L6tSoc_BJECPebWKRXjBZCiFV4n3oknjhMstn64tZ_2W-5JsGY4Hc5n9yBXArwl93lqt7_RN5w6Cf0h4QyQ5v-65YGjQR0_FDW2QvzqY368QQMicAtaSqzs8KJZgnYb9c7d0zgdAZHzu6qMQvRL5hajrn1n91CbOpbISD08qNLyrdkt-bFTWhAI4vMQFh6WeZu0fM4lFd2NcRwr3XPksINHaQ-G_xBniIqbw0Ls1jF44-csFCur-kEgU8awapJzKnqDKgw',
            'e' => 'AQAB',
        ], 'RS256')->getKeyMaterial();

        $this->app['config']->set('passport.public_key', openssl_pkey_get_details($key)['key']);

        $this->getJson('/oauth/jwks')
            ->assertOk()
            ->assertJsonPath('keys.0.kid', 'NzbLsXh8uDCcd-6MNwXF4W_7noWXFZAfHkxZsRGC9Xs');
    }

    public function test_issued_access_token_can_be_verified_with_published_key()
    {
        $client = ClientFactory::new()->asClientCredentials()->create();

        $token = $this->post('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->getKey(),
            'client_secret' => $client->plainSecret,
        ])->assertOk()->json('access_token');

        $keys = JWK::parseKeySet($this->getJson('/oauth/jwks')->json());

        $claims = JWT::decode($token, reset($keys));

        $this->assertSame($client->getKey(), $claims->aud);
    }

    public function test_configured_public_key_is_published()
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        $this->app['config']->set('passport.public_key', openssl_pkey_get_details($key)['key']);

        $this->getJson('/oauth/jwks')
            ->assertOk()
            ->assertJsonPath('keys.0.n', rtrim(strtr(base64_encode(openssl_pkey_get_details($key)['rsa']['n']), '+/', '-_'), '='));
    }

    public function test_key_set_is_empty_when_public_key_is_invalid()
    {
        $this->app['config']->set('passport.public_key', 'not-a-key');

        $this->getJson('/oauth/jwks')->assertOk()->assertExactJson(['keys' => []]);
    }

    public function test_key_set_is_empty_when_public_key_is_missing()
    {
        Passport::loadKeysFrom(__DIR__.'/missing');

        $this->getJson('/oauth/jwks')->assertOk()->assertExactJson(['keys' => []]);
    }
}
