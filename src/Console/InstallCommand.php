<?php

namespace Laravel\Passport\Console;

use Illuminate\Console\Command;
use Laravel\Passport\Passport;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:install
                            {--uuids : Use UUIDs for all client IDs}
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
     *
     * @return void
     */
    public function handle()
    {
        $provider = in_array('users', array_keys(config('auth.providers'))) ? 'users' : null;

        $this->call('passport:keys', ['--force' => $this->option('force'), '--length' => $this->option('length')]);

        if ($this->option('uuids')) {
            $this->configureUuids();
        }

        $this->call('passport:client', ['--personal' => true, '--name' => config('app.name').' Personal Access Client']);
        $this->call('passport:client', ['--password' => true, '--name' => config('app.name').' Password Grant Client', '--provider' => $provider]);
    }

    /**
     * Configure Passport for client UUIDs.
     *
     * @return void
     */
    protected function configureUuids()
    {
        $this->call('vendor:publish', ['--tag' => 'passport-config']);
        $this->call('vendor:publish', ['--tag' => 'passport-migrations']);

        config(['passport.client_uuids' => true]);
        Passport::setClientUuids(true);

        $this->replaceInFile(config_path('passport.php'), '\'client_uuids\' => false', '\'client_uuids\' => true');
        $this->replaceInFile(database_path('migrations/2016_06_01_000001_create_oauth_auth_codes_table.php'), '$table->unsignedBigInteger(\'client_id\');', '$table->uuid(\'client_id\');');
        $this->replaceInFile(database_path('migrations/2016_06_01_000002_create_oauth_access_tokens_table.php'), '$table->unsignedBigInteger(\'client_id\');', '$table->uuid(\'client_id\');');
        $this->replaceInFile(database_path('migrations/2016_06_01_000004_create_oauth_clients_table.php'), '$table->bigIncrements(\'id\');', '$table->uuid(\'id\')->primary();');
        $this->replaceInFile(database_path('migrations/2016_06_01_000005_create_oauth_personal_access_clients_table.php'), '$table->unsignedBigInteger(\'client_id\');', '$table->uuid(\'client_id\');');

        if ($this->confirm('In order to finish configuring client UUIDs, we need to rebuild the Passport database tables. Would you like to rollback and re-run your last migration?')) {
            $this->call('migrate:rollback');
            $this->call('migrate');
            $this->line('');
        }
    }

    /**
     * Replace a given string in a given file.
     *
     * @param  string  $path
     * @param  string  $search
     * @param  string  $replace
     * @return void
     */
    protected function replaceInFile($path, $search, $replace)
    {
        file_put_contents(
            $path,
            str_replace($search, $replace, file_get_contents($path))
        );
    }
}
