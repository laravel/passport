<?php

namespace Laravel\Passport\Console;

use DateTime;
use Laravel\Passport\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\PersonalAccessClient;

class ClientsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:clients
            {--personal : Show ONLY the personal clients}
            {--password : Show ONLY the password clients}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists the registered passport clients';

    /**
     * The table headers for the command results
     * @var array
     */
    protected $headers = [
        'Id',
        'User_id',
        'Name',
        'Redirect',
        'Personal?',
        'Password?',
        'Revoked',
        'Created_at',
        'Updated_at',
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->displayClients($this->getClients());
    }

    /**
     * Obtain the list of personal clients
     * @return Illuminate\Support\Collection
     */
    protected function obtainPersonalClients()
    {
        return Client::where('personal_access_client', true)->get();
    }

    /**
     * Obtain the list of password clients
     * @return Illuminate\Support\Collection
     */
    protected function obtainPasswordClients()
    {
        return Client::where('password_client', true)->get();
    }

    /**
     * Obtain the list of clients
     * @return Illuminate\Support\Collection
     */
    protected function obtainAllClients()
    {
        return Client::all();
    }

    /**
     * Display the clients information on the console.
     *
     * @param  array  $clients
     * @return void
     */
    protected function displayClients(Collection $clients)
    {
        $this->table($this->headers, $clients);
    }

    protected function getClients()
    {
        if ($this->option('personal')) {
            return $this->obtainPersonalClients();
        }

        if ($this->option('password')) {
            return $this->obtainPasswordClients();
        }

        return $this->obtainAllClients();
    }
}
