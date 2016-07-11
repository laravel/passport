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
    protected $signature = 'passport:client';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a client for issuing personal access tokens';

    /**
     * Execute the console command.
     *
     * @param  \Laravel\Passport\ClientRepository  $clients
     * @return mixed
     */
    public function handle(ClientRepository $clients)
    {
        $this->createPersonalAccessClient($this->createClient($clients, $this->ask(
            'What should we name the client?',
            config('app.name').' Personal Access Tokens'
        )));

        $this->info('Personal access token client created successfully.');
    }

    /**
     * Create a client with the given name.
     *
     * @param  string  $name
     * @return int
     */
    protected function createClient(ClientRepository $clients, $name)
    {
        return $clients->create(null, $name, 'http://localhost', true)->id;
    }

    /**
     * Create the personal access client record.
     *
     * @param  int  $clientId
     * @return void
     */
    protected function createPersonalAccessClient($clientId)
    {
        DB::table('oauth_personal_access_clients')->insert([
            'client_id' => $clientId,
            'created_at' => new DateTime,
            'updated_at' => new DateTime,
        ]);
    }
}
