<?php

namespace Laravel\Passport\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'passport:install')]
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:install
                            {--force : Overwrite keys they already exist}
                            {--length=4096 : The length of the private key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the commands necessary to prepare Passport for use';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->call('passport:keys', [
            '--force' => $this->option('force'),
            '--length' => $this->option('length'),
        ]);

        $this->call('vendor:publish', ['--tag' => 'passport-config']);
                
        // Selectively publish Passport migrations (plain PHP)
        $migrationPath = database_path('migrations');
        $passportMigrationPath = base_path('vendor/laravel/passport/database/migrations');

        // Build a list of all migration stubs in the package
        $stubFiles = glob($passportMigrationPath . '/*.php');

        $this->components->info('Checking Passport migrations…');

        foreach ($stubFiles as $stubPath) {
            // Strip the timestamp from the stub filename
            $stubFilename = basename($stubPath);
            $baseName = preg_replace('/^\d+_\d+_\d+_\d+_/', '_', $stubFilename);

            // Check if a migration with this base name already exists
            $existing = glob($migrationPath . '/*' . $baseName);

            if (empty($existing)) {
                // No migration yet → copy it with a new timestamp
                $newName = date('Y_m_d_His') . $baseName;
                $target = $migrationPath . '/' . $newName;
                copy($stubPath, $target);

                $this->components->info("Published Passport migration: {$newName}");
                usleep(100000); // small delay so timestamps differ
            } else {
                $this->components->warn("Skipped existing Passport migration: {$baseName}");
            }
        }

        if ($this->components->confirm('Would you like to run all pending database migrations?', true)) {
            $this->call('migrate');

            if ($this->components->confirm('Would you like to create the "personal access" grant client?', true)) {
                $this->call('passport:client', [
                    '--personal' => true,
                    '--name' => config('app.name'),
                ]);
            }
        }
    }
}
