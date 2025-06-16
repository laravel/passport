<?php

namespace Laravel\Passport\Tests\Feature\Console;

use Illuminate\Support\Facades\File;
use Laravel\Passport\Tests\Feature\PassportTestCase;

class InstallCommandTest extends PassportTestCase
{
    public function test_migrations_are_published_if_they_do_not_exist()
    {
        File::shouldReceive('glob')
            ->once()
            ->with(database_path('migrations/*_create_oauth_*.php'))
            ->andReturn([]);

        // Run the passport:install command
        $this->artisan('passport:install --no-interaction --force')
            ->assertExitCode(0);
    }

    public function test_migrations_are_skipped_if_already_exist()
    {
        File::shouldReceive('glob')
            ->once()
            ->with(database_path('migrations/*_create_oauth_*.php'))
            ->andReturn([
                database_path('migrations/2025_01_01_000000_create_oauth_clients_table.php'),
            ]);

        $this->artisan('passport:install --no-interaction --force')
            ->assertExitCode(0);
    }
}
