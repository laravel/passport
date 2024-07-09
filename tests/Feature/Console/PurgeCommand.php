<?php

namespace Console;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

class PurgeCommand extends TestCase
{
    use WithWorkbench;

    public function test_it_can_purge_tokens()
    {
        $this->travelTo(Carbon::create(2000, 1, 8));

        $query = DB::pretend(function () {
            $this->artisan('passport:purge')
                ->expectsOutputToContain('Purged revoked items and items expired for more than 1 week.');
        });

        $this->assertSame([
            'delete from "oauth_access_tokens" where ("revoked" = 1 or "expires_at" < \'2000-01-01 00:00:00\')',
            'delete from "oauth_auth_codes" where ("revoked" = 1 or "expires_at" < \'2000-01-01 00:00:00\')',
            'delete from "oauth_refresh_tokens" where ("revoked" = 1 or "expires_at" < \'2000-01-01 00:00:00\')',
        ], array_column($query, 'query'));
    }

    public function test_it_can_purge_revoked_tokens()
    {
        $query = DB::pretend(function () {
            $this->artisan('passport:purge', ['--revoked' => true])
                ->expectsOutputToContain('Purged revoked items.');
        });

        $this->assertSame([
            'delete from "oauth_access_tokens" where ("revoked" = 1)',
            'delete from "oauth_auth_codes" where ("revoked" = 1)',
            'delete from "oauth_refresh_tokens" where ("revoked" = 1)',
        ], array_column($query, 'query'));
    }

    public function test_it_can_purge_expired_tokens()
    {
        $this->travelTo(Carbon::create(2000, 1, 8));

        $query = DB::pretend(function () {
            $this->artisan('passport:purge', ['--expired' => true])
                ->expectsOutputToContain('Purged items expired for more than 1 week.');
        });

        $this->assertSame([
            'delete from "oauth_access_tokens" where ("expires_at" < \'2000-01-01 00:00:00\')',
            'delete from "oauth_auth_codes" where ("expires_at" < \'2000-01-01 00:00:00\')',
            'delete from "oauth_refresh_tokens" where ("expires_at" < \'2000-01-01 00:00:00\')',
        ], array_column($query, 'query'));
    }

    public function test_it_can_purge_revoked_and_expired_tokens()
    {
        $this->travelTo(Carbon::create(2000, 1, 8));

        $query = DB::pretend(function () {
            $this->artisan('passport:purge', ['--revoked' => true, '--expired' => true])
                ->expectsOutputToContain('Purged revoked items and items expired for more than 1 week.');
        });

        $this->assertSame([
            'delete from "oauth_access_tokens" where ("revoked" = 1 or "expires_at" < \'2000-01-01 00:00:00\')',
            'delete from "oauth_auth_codes" where ("revoked" = 1 or "expires_at" < \'2000-01-01 00:00:00\')',
            'delete from "oauth_refresh_tokens" where ("revoked" = 1 or "expires_at" < \'2000-01-01 00:00:00\')',
        ], array_column($query, 'query'));
    }

    public function test_it_can_purge_tokens_by_hours()
    {
        $this->travelTo(Carbon::create(2000, 1, 1, 2));

        $query = DB::pretend(function () {
            $this->artisan('passport:purge', ['--hours' => 2])
                ->expectsOutputToContain('Purged revoked items and items expired for more than 2 hours.');
        });

        $this->assertSame([
            'delete from "oauth_access_tokens" where ("revoked" = 1 or "expires_at" < \'2000-01-01 00:00:00\')',
            'delete from "oauth_auth_codes" where ("revoked" = 1 or "expires_at" < \'2000-01-01 00:00:00\')',
            'delete from "oauth_refresh_tokens" where ("revoked" = 1 or "expires_at" < \'2000-01-01 00:00:00\')',
        ], array_column($query, 'query'));
    }
}
