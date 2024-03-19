<?php

namespace Laravel\Passport\Console;

use Illuminate\Console\Command;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'passport:client')]
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
            {--name= : The name of the client}
            {--provider= : The name of the user provider}
            {--redirect_uri= : The URI to redirect to after authorization }
            {--user_id= : The user ID the client should be assigned to }
            {--public : Create a public client (Auth code grant type only) }';

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
            $this->createPersonalClient($clients);
        } elseif ($this->option('password')) {
            $this->createPasswordClient($clients);
        } elseif ($this->option('client')) {
            $this->createClientCredentialsClient($clients);
        } else {
            $this->createAuthCodeClient($clients);
        }
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

        $this->components->info('Personal access client created successfully.');

        $this->outputClientDetails($client);
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

        $providers = array_keys(config('auth.providers'));

        $provider = $this->option('provider') ?: $this->choice(
            'Which user provider should this client use to retrieve users?',
            $providers,
            in_array('users', $providers) ? 'users' : null
        );

        $client = $clients->createPasswordGrantClient(
            null, $name, 'http://localhost', $provider
        );

        $this->components->info('Password grant client created successfully.');

        $this->outputClientDetails($client);
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
            'What should we name the client?',
            config('app.name').' ClientCredentials Grant Client'
        );

        $client = $clients->create(
            null, $name, ''
        );

        $this->components->info('New client created successfully.');

        $this->outputClientDetails($client);
    }

    /**
     * Create a authorization code client.
     *
     * @param  \Laravel\Passport\ClientRepository  $clients
     * @return void
     */
    protected function createAuthCodeClient(ClientRepository $clients)
    {
        $userId = $this->option('user_id') ?: $this->ask(
            'Which user ID should the client be assigned to? (Optional)'
        );

        $name = $this->option('name') ?: $this->ask(
            'What should we name the client?'
        );

        $redirect = $this->option('redirect_uri') ?: $this->ask(
            'Where should we redirect the request after authorization?',
            url('/auth/callback')
        );

        $client = $clients->create(
            $userId, $name, $redirect, null, false, false, ! $this->option('public')
        );

        $this->components->info('New client created successfully.');

        $this->outputClientDetails($client);
    }

    /**
     * Output the client's ID and secret key.
     *
     * @param  \Laravel\Passport\Client  $client
     * @return void
     */
    protected function outputClientDetails(Client $client)
    {
        if (Passport::$hashesClientSecrets) {
            $this->line('<comment>Here is your new client secret. This is the only time it will be shown so don\'t lose it!</comment>');
            $this->line('');
        }

        $this->components->twoColumnDetail('<comment>Client ID</comment>', $client->getKey());
        $this->components->twoColumnDetail('<comment>Client secret</comment>', $client->plainSecret);
    }
}
