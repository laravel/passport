<?php

namespace Laravel\Passport\Console;

use Illuminate\Console\Command;
use Laravel\Passport\Passport;

class HashCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:hash';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hash all of the existing secrets in the clients table';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! Passport::$hashesClientSecrets) {
            $this->warn("Warning! You haven't enabled client hashing yet in your AppServiceProvider.");

            return;
        }

        if ($this->confirm('Are you sure you want to hash ALL client secrets? This cannot be undone.')) {
            $model = Passport::clientModel();

            foreach ((new $model)->whereNotNull('secret')->cursor() as $client) {
                $client->timestamps = false;

                $client->forceFill([
                    'secret' => password_hash($client->secret, PASSWORD_BCRYPT),
                ])->save();
            }

            $this->info('All OAuth client secrets were successfully hashed.');
        }
    }
}
