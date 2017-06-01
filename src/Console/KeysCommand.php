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
    protected $signature = 'passport:keys {--force}';

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

        $oauth_private = Passport::keyPath('oauth-private.key');
        $oauth_public  = Passport::keyPath('oauth-public.key');

        if((file_exists($oauth_private) || file_exists($oauth_public)) && !$this->option('force')) {
            $this->error("You already have keys. Please use the force to overwrite them");
            return false;
        }

        file_put_contents($oauth_private, array_get($keys, 'privatekey'));
        file_put_contents($oauth_public, array_get($keys, 'publickey'));

        $this->info('Encryption keys generated successfully.');
    }
}
