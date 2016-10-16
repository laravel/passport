<?php

namespace Laravel\Passport\Console;

use phpseclib\Crypt\RSA;
use Laravel\Passport\Passport;
use Illuminate\Console\Command;

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
     * @param  RSA  $rsa
     * @return mixed
     */
    public function handle(RSA $rsa)
    {
        $keys = $rsa->createKey(4096);

        file_put_contents(Passport::keyPath('oauth-private.key'), array_get($keys, 'privatekey'));
        file_put_contents(Passport::keyPath('oauth-public.key'), array_get($keys, 'publickey'));

        $this->info('Encryption keys generated successfully.');
    }
}
