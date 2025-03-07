<?php

namespace Laravel\Passport\Console;

use Illuminate\Console\Command;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
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
            {--implicit : Create an implicit grant client}
            {--device : Create a device authorization grant client}
            {--name= : The name of the client}
            {--provider= : The name of the user provider}
            {--redirect_uri= : The URI to redirect to after authorization }
            {--public : Create a public client (without secret) }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a client for issuing access tokens';

    /**
     * Execute the console command.
     */
    public function handle(ClientRepository $clients): void
    {
        if (! $this->option('name')) {
            $this->input->setOption('name', $this->components->ask(
                'What should we name the client?',
                config('app.name')
            ));
        }

        $client = match (true) {
            $this->option('personal') => $this->createPersonalAccessClient($clients),
            $this->option('password') => $this->createPasswordClient($clients),
            $this->option('client') => $this->createClientCredentialsClient($clients),
            $this->option('implicit') => $this->createImplicitClient($clients),
            $this->option('device') => $this->createDeviceCodeClient($clients),
            default => $this->createAuthCodeClient($clients)
        };

        $this->components->info('New client created successfully.');

        if ($client) {
            $this->components->twoColumnDetail('Client ID', $client->getKey());

            if ($client->confidential()) {
                $this->components->twoColumnDetail('Client Secret', $client->plainSecret);
                $this->components->warn('The client secret will not be shown again, so don\'t lose it!');
            }
        }
    }

    /**
     * Create a new personal access client.
     */
    protected function createPersonalAccessClient(ClientRepository $clients): ?Client
    {
        $provider = $this->option('provider') ?: $this->components->choice(
            'Which user provider should this client use to retrieve users?',
            collect(config('auth.guards'))->where('driver', 'passport')->pluck('provider')->all(),
            config('auth.guards.api.provider')
        );

        $clients->createPersonalAccessGrantClient($this->option('name'), $provider);

        return null;
    }

    /**
     * Create a new password grant client.
     */
    protected function createPasswordClient(ClientRepository $clients): Client
    {
        $provider = $this->option('provider') ?: $this->components->choice(
            'Which user provider should this client use to retrieve users?',
            collect(config('auth.guards'))->where('driver', 'passport')->pluck('provider')->all(),
            config('auth.guards.api.provider')
        );

        $confidential = $this->hasOption('public')
            ? ! $this->option('public')
            : $this->components->confirm('Would you like to make this client confidential?');

        return $clients->createPasswordGrantClient($this->option('name'), $provider, $confidential);
    }

    /**
     * Create a client credentials grant client.
     */
    protected function createClientCredentialsClient(ClientRepository $clients): Client
    {
        return $clients->createClientCredentialsGrantClient($this->option('name'));
    }

    /**
     * Create an implicit grant client.
     */
    protected function createImplicitClient(ClientRepository $clients): Client
    {
        $redirect = $this->option('redirect_uri') ?: $this->components->ask(
            'Where should we redirect the request after authorization?',
            url('/auth/callback')
        );

        return $clients->createImplicitGrantClient($this->option('name'), explode(',', $redirect));
    }

    /**
     * Create a device code client.
     */
    protected function createDeviceCodeClient(ClientRepository $clients): Client
    {
        $confidential = $this->hasOption('public')
            ? ! $this->option('public')
            : $this->confirm('Would you like to make this client confidential?', true);

        return $clients->createDeviceAuthorizationGrantClient($this->option('name'), $confidential);
    }

    /**
     * Create an authorization code client.
     */
    protected function createAuthCodeClient(ClientRepository $clients): Client
    {
        $redirect = $this->option('redirect_uri') ?: $this->components->ask(
            'Where should we redirect the request after authorization?',
            url('/auth/callback')
        );

        $confidential = $this->hasOption('public')
            ? ! $this->option('public')
            : $this->components->confirm('Would you like to make this client confidential?', true);

        $enableDeviceFlow = $this->confirm('Would you like to enable the device authorization flow for this client?');

        return $clients->createAuthorizationCodeGrantClient(
            $this->option('name'), explode(',', $redirect), $confidential, null, $enableDeviceFlow
        );
    }
}
