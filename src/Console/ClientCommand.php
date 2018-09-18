<?php

namespace Laravel\Passport\Console;

use Illuminate\Console\Command;
use Laravel\Passport\ClientRepository;

class ClientCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:client
            {--personal : Create a personal access token client}
            {--password : Create a password grant client}
            {--client : Create a client credentials grant client}
            {--name= : The name of the client}';

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
            return $this->createPersonalClient($clients);
        }

        if ($this->option('password')) {
            return $this->createPasswordClient($clients);
        }

        if ($this->option('client')) {
            return $this->createClientCredentialsClient($clients);
        }

        $this->createAuthCodeClient($clients);
    }

    /**
     * Create a new personal access client.
     *
     * @param  \Laravel\Passport\ClientRepository  $clients
     * @return void
     */
    protected function createPersonalClient(ClientRepository $clients)
    {
        $name = $this->option('name') ?: $this->ask(
            'What should we name the personal access client?',
            config('app.name').' Personal Access Client'
        );

        $client = $clients->createPersonalAccessClient(
            null, $name, 'http://localhost'
        );

        $this->reportCommandLog(
            'Personal access client created successfully.',
            $client->id,
            $client->secret);
    }

    /**
     * Create a new password grant client.
     *
     * @param  \Laravel\Passport\ClientRepository  $clients
     * @return void
     */
    protected function createPasswordClient(ClientRepository $clients)
    {
        $name = $this->option('name') ?: $this->ask(
            'What should we name the password grant client?',
            config('app.name').' Password Grant Client'
        );

        $client = $clients->createPasswordGrantClient(
            null, $name, 'http://localhost'
        );

        $this->reportCommandLog(
            'Password grant client created successfully.',
            $client->id,
            $client->secret);
    }

    /**
     * Create a authorization code client.
     *
     * @param  \Laravel\Passport\ClientRepository  $clients
     * @return void
     */
    protected function createAuthCodeClient(ClientRepository $clients)
    {
        $userId = $this->ask(
            'Which user ID should the client be assigned to?'
        );

        $name = $this->option('name') ?: $this->ask(
            'What should we name the client?'
        );

        $redirect = $this->ask(
            'Where should we redirect the request after authorization?',
            url('/auth/callback')
        );

        $client = $clients->create(
            $userId, $name, $redirect
        );

        $this->reportCommandLog('New client created successfully.', $client->id, $client->secret);
    }

    /**
     * Create a client credentials grant client.
     *
     * @param  \Laravel\Passport\ClientRepository  $clients
     * @return void
     */
    protected function createClientCredentialsClient(ClientRepository $clients)
    {
        $name = $this->option('name') ?: $this->ask(
            'What should we name the client?'
        );

        $client = $clients->create(
            null, $name, ''
        );

        $this->reportCommandLog('New client created successfully.', $client->id, $client->secret);
    }

    /**
     * @param string $message
     * @param int $clientId
     * @param string $clientSecret
     */
    protected function reportCommandLog(string $message, int $clientId, string $clientSecret)
    {
        $this->info($message);
        $this->line('<comment>Client ID:</comment> '.$clientId);
        $this->line('<comment>Client secret:</comment> '.$clientSecret);
    }
}
