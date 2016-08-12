<?php

namespace Laravel\Passport\Console;

use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;

class ClientCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:client {--personal : Create a personal access token client}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a client for issuing access tokens';

    /**
     * Execute the console command.
     *
     * @param  \Laravel\Passport\ClientRepository  $clients
     * @return void
     */
    public function handle(ClientRepository $clients)
    {
        if ($this->option('personal')) {
            return $this->handlePersonal($clients);
        }

        $userId = $this->ask(
            'Which user ID should the client be assigned to?'
        );

        $name = $this->ask(
            'What should we name the client?'
        );

        $redirect = $this->ask(
            'Where should we redirect the request after authorization?',
            url('/auth/callback')
        );

        $client = $clients->create(
            $userId, $name, $redirect
        );

        $this->info('New client created successfully.');
        $this->line('<comment>Client ID:</comment> '.$client->id);
        $this->line('<comment>Client secret:</comment> '.$client->secret);
    }

    /**
     * Create a new personal access client.
     *
     * @param  \Laravel\Passport\ClientRepository  $clients
     * @return void
     */
    protected function handlePersonal(ClientRepository $clients)
    {
        $name = $this->ask(
            'What should we name the personal access token client?',
            config('app.name').' Personal Access Tokens'
        );

        $client = $clients->create(
            null, $name, 'http://localhost', true
        );

        DB::table('oauth_personal_access_clients')->insert([
            'client_id' => $client->id,
            'created_at' => new DateTime,
            'updated_at' => new DateTime,
        ]);

        $this->info('Personal access token client created successfully.');
    }
}
