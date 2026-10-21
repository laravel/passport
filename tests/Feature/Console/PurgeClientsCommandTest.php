<?php

namespace Laravel\Passport\Tests\Feature\Console;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Passport\Client;
use Laravel\Passport\Database\Factories\ClientFactory;
use Laravel\Passport\Passport;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\Database\Factories\UserFactory;

class PurgeClientsCommandTest extends TestCase
{
    use LazilyRefreshDatabase, WithWorkbench;

    public function test_it_can_purge_unused_clients()
    {
        $this->travelTo(Carbon::create(2000, 1, 8));

        $unused = ClientFactory::new()->asPublic()->create(['created_at' => Carbon::create(2000, 1, 1)->subSecond()]);
        $recent = ClientFactory::new()->asPublic()->create(['created_at' => Carbon::create(2000, 1, 1)->addSecond()]);

        $this->artisan('passport:purge', ['--clients' => true])
            ->expectsOutputToContain('Purged revoked items, items expired for more than 1 week and unused clients created more than 1 week ago.');

        $this->assertModelMissing($unused);
        $this->assertModelExists($recent);
    }

    public function test_it_can_purge_unused_clients_by_hours()
    {
        $this->travelTo(Carbon::create(2000, 1, 1, 3));

        $unused = ClientFactory::new()->asPublic()->create(['created_at' => Carbon::create(2000, 1, 1, 0, 59)]);
        $recent = ClientFactory::new()->asPublic()->create(['created_at' => Carbon::create(2000, 1, 1, 1, 1)]);

        $this->artisan('passport:purge', ['--clients' => true, '--revoked' => true, '--hours' => 2])
            ->expectsOutputToContain('Purged revoked items and unused clients created more than 2 hours ago.');

        $this->assertModelMissing($unused);
        $this->assertModelExists($recent);
    }

    public function test_it_does_not_purge_clients_without_the_option()
    {
        $this->travelTo(Carbon::create(2000, 1, 8));

        $unused = ClientFactory::new()->asPublic()->create(['created_at' => Carbon::create(1999, 1, 1)]);

        $this->artisan('passport:purge')
            ->expectsOutputToContain('Purged revoked items and items expired for more than 1 week.');

        $this->assertModelExists($unused);
    }

    public function test_it_keeps_clients_that_have_been_used()
    {
        $this->travelTo(Carbon::create(2000, 1, 8));

        $old = Carbon::create(1999, 1, 1);

        // Each client's only token or code is expired long ago, so this same run purges it.
        $withToken = ClientFactory::new()->asPublic()->create(['created_at' => $old]);
        $token = Passport::token()->newQuery()->forceCreate([
            'id' => Str::random(80), 'client_id' => $withToken->getKey(), 'revoked' => false, 'expires_at' => $old,
        ]);

        $withAuthCode = ClientFactory::new()->asPublic()->create(['created_at' => $old]);
        $authCode = Passport::authCode()->newQuery()->forceCreate([
            'id' => Str::random(80), 'user_id' => 1, 'client_id' => $withAuthCode->getKey(), 'revoked' => false, 'expires_at' => $old,
        ]);

        $withDeviceCode = ClientFactory::new()->asPublic()->asDeviceCodeClient()->create(['created_at' => $old]);
        $deviceCode = Passport::deviceCode()->newQuery()->forceCreate([
            'id' => Str::random(80), 'client_id' => $withDeviceCode->getKey(), 'user_code' => 'ABCD1234', 'scopes' => [], 'revoked' => false, 'expires_at' => $old,
        ]);

        $unused = ClientFactory::new()->asPublic()->create(['created_at' => $old]);

        $this->artisan('passport:purge', ['--clients' => true]);

        $this->assertModelExists($withToken);
        $this->assertModelExists($withAuthCode);
        $this->assertModelExists($withDeviceCode);
        $this->assertModelMissing($unused);

        $this->assertModelMissing($token);
        $this->assertModelMissing($authCode);
        $this->assertModelMissing($deviceCode);
    }

    public function test_it_keeps_clients_that_may_belong_to_someone()
    {
        $this->travelTo(Carbon::create(2000, 1, 8));

        $old = ['created_at' => Carbon::create(1999, 1, 1)];

        $kept = [
            'owned' => ClientFactory::new()->asPublic()->for(UserFactory::new(), 'owner')->create($old),
            'confidential' => ClientFactory::new()->create($old),
            'revoked' => ClientFactory::new()->asPublic()->create(['revoked' => true, ...$old]),
            'personal access' => ClientFactory::new()->asPersonalAccessTokenClient()->create($old),
            'public personal access' => ClientFactory::new()->asPersonalAccessTokenClient()->asPublic()->create($old),
            'public password' => ClientFactory::new()->asPasswordClient()->asPublic()->create($old),
            'public client credentials' => ClientFactory::new()->asClientCredentials()->asPublic()->create($old),
        ];

        $this->artisan('passport:purge', ['--clients' => true]);

        foreach ($kept as $name => $client) {
            $this->assertTrue(Passport::client()->newQuery()->whereKey($client->getKey())->exists(), "The {$name} client was purged.");
        }
    }

    public function test_it_keeps_clients_with_a_legacy_schema()
    {
        $this->travelTo(Carbon::create(2000, 1, 8));

        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('redirect')->nullable();
            $table->boolean('personal_access_client')->default(false);
            $table->boolean('password_client')->default(false);
            $table->dropColumn(['grant_types', 'redirect_uris']);
        });

        $create = fn (array $attributes = []): Client => tap(Passport::client()->forceFill([
            'name' => 'Legacy', 'secret' => null, 'redirect' => 'https://localhost', 'revoked' => false, 'created_at' => Carbon::create(1999, 1, 1), ...$attributes,
        ]))->save();

        $unused = $create();
        $owned = $create(['user_id' => 1]);
        $personalAccess = $create(['personal_access_client' => true]);
        $password = $create(['password_client' => true]);

        $this->artisan('passport:purge', ['--clients' => true]);

        $this->assertModelMissing($unused);
        $this->assertModelExists($owned);
        $this->assertModelExists($personalAccess);
        $this->assertModelExists($password);
    }
}
