<?php

namespace Laravel\Passport\Tests\Feature\Console;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Str;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Laravel\Passport\Tests\Feature\PassportTestCase;

class HashCommand extends PassportTestCase
{
    public function test_it_can_properly_hash_client_secrets()
    {
        $client = factory(Client::class)->create(['secret' => $secret = Str::random(40)]);
        $hasher = $this->app->make(Hasher::class);

        Passport::hashClientSecrets();

        $this->artisan('passport:hash', ['--force' => true]);

        $this->assertTrue($hasher->check($secret, $client->refresh()->secret));

        Passport::$hashesClientSecrets = false;
    }
}
