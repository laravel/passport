<?php

namespace Laravel\Passport\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'passport:hash')]
class HashCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:hash {--force : Force the operation to run without confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hash all of the existing secrets in the clients table';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if ($this->option('force') ||
            $this->components->confirm('Are you sure you want to hash all client secrets? This cannot be undone.')) {
            foreach (Passport::client()->newQuery()->whereNotNull('secret')->cursor() as $client) {
                if (Hash::isHashed($client->secret) && ! Hash::needsRehash($client->secret)) {
                    continue;
                }

                $client->timestamps = false;

                $client->forceFill([
                    'secret' => $client->secret,
                ])->save();
            }

            $this->components->info('All client secrets were successfully hashed.');
        }
    }
}
