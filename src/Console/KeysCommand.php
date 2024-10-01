<?php

namespace Laravel\Passport\Console;

use Illuminate\Console\Command;
use Laravel\Passport\Passport;
use phpseclib3\Crypt\RSA;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'passport:keys')]
class KeysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:keys
                                      {--force : Overwrite keys they already exist}
                                      {--length=4096 : The length of the private key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the encryption keys for API authentication';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        [$publicKey, $privateKey] = [
            Passport::keyPath('oauth-public.key'),
            Passport::keyPath('oauth-private.key'),
        ];

        if ((file_exists($publicKey) || file_exists($privateKey)) && ! $this->option('force')) {
            $this->components->error('Encryption keys already exist. Use the --force option to overwrite them.');

            return Command::FAILURE;
        }

        $key = RSA::createKey((int) $this->option('length'));

        file_put_contents($publicKey, (string) $key->getPublicKey());
        file_put_contents($privateKey, (string) $key);

        if (! windows_os()) {
            chmod($publicKey, 0660);
            chmod($privateKey, 0600);
        }

        $this->components->info('Encryption keys generated successfully.');

        return Command::SUCCESS;
    }
}
