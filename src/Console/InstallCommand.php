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
        $this->call('vendor:publish', ['--tag' => 'passport-migrations']);

        if ($this->confirm('Would you like to run all pending database migrations?', true)) {
            $this->call('migrate');

            if ($this->confirm('Would you like to create the "personal access" grant client?', true)) {
                $this->call('passport:client', [
                    '--personal' => true,
                    '--name' => config('app.name'),
                ]);
            }
        }
    }
}
