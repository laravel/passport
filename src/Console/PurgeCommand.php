<?php

namespace Laravel\Passport\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Laravel\Passport\AuthCode;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

class PurgeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:purge
                            {--revoked : Only purge revoked tokens and authentication codes}
                            {--expired : Only purge expired tokens and authentication codes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge revoked and / or expired tokens and authentication codes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expired = Carbon::now()->subDays(7);

        if (($this->option('revoked') && $this->option('expired')) ||
            (! $this->option('revoked') && ! $this->option('expired'))) {
            Token::where('revoked', 1)->orWhereDate('expires_at', '<', $expired)->delete();
            AuthCode::where('revoked', 1)->orWhereDate('expires_at', '<', $expired)->delete();
            RefreshToken::where('revoked', 1)->orWhereDate('expires_at', '<', $expired)->delete();

            $this->info('Purged revoked items and items expired for more than seven days.');
        } elseif ($this->option('revoked')) {
            Token::where('revoked', 1)->delete();
            AuthCode::where('revoked', 1)->delete();
            RefreshToken::where('revoked', 1)->delete();

            $this->info('Purged revoked items.');
        } elseif ($this->option('expired')) {
            Token::whereDate('expires_at', '<', $expired)->delete();
            AuthCode::whereDate('expires_at', '<', $expired)->delete();
            RefreshToken::whereDate('expires_at', '<', $expired)->delete();

            $this->info('Purged items expired for more than seven days.');
        }
    }
}
