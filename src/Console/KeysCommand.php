<?php

namespace Laravel\Passport\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class KeysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the encryption keys for API authentication';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $callback = function ($type, $line) {
            $this->output->write($line);
        };

        (new Process('openssl genrsa -out oauth-private.key 4096', storage_path()))->run($callback);
        (new Process('openssl rsa -in oauth-private.key -pubout -out oauth-public.key', storage_path()))->run($callback);

        $this->info('Encryption keys generated successfully.');
    }
}
