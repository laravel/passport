<?php

namespace Laravel\Passport\Console;

use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Illuminate\Console\Command;

class UUIDCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:uuid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate version 4 UUIDs for existing Passport clients';

    /**
     * Only show this command if Passport::useClientUUIDs is true
     *
     * @return null
     */
    public function __construct()
    {
        if (!Passport::$useClientUUIDs) {
            exit;
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->comment('Generating version 4 UUIDs for Passport clients...');

        $clients = Client::where('uuid', null)->get();

        foreach($clients as $client) {
            $client->uuid = UUID::generate(4)->string;
            $client->save();
        }

        $this->line('✓ Done.');
    }
}
