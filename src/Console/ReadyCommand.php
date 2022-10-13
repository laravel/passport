<?php

namespace Laravel\Passport\Console;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;

class ReadyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:ready';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks if Passport has already been installed in this environment';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        [$publicKey, $privateKey] = [
            Passport::keyPath('oauth-public.key'),
            Passport::keyPath('oauth-private.key'),
        ];

        if (! (file_exists($publicKey) && file_exists($privateKey))) {
            $this->error('Passport keys are missing. Please run "php artisan passport:install" to generate them.');

            return Command::FAILURE;
        }

        $accessExists = $this->clientExists('Personal Access Client');
        $grantExists = $this->clientExists('Password Grant Client');

        if (! ($accessExists && $grantExists)) {
            $this->error('Passport clients are missing. Please run "php artisan passport:install" to generate them');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function clientExists(string $name)
    {
        try {
            return DB::table("oauth_clients")
                ->where("name", config('app.name').' '.$name)
                ->exists();
        } catch (QueryException $e) {
            $this->error('Passport migrations have not been run. Please run "php artisan migrate" to run them');
        }

        return false;
    }
}