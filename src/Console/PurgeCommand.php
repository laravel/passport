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
                            {--revoked : Only purge revoked tokens and auth codes}
                            {--expired : Only purge expired tokens and auth codes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purges revoked and/or expired tokens and auth codes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $options = $this->options();
        $now = Carbon::now();
        if (
            ($options['revoked'] && $options['expired']) ||
            (! $options['revoked'] && ! $options['expired'])
        ) {
            Token::where('revoked', 1)->orWhereDate('expires_at', '<', $now)->delete();
            AuthCode::where('revoked', 1)->orWhereDate('expires_at', '<', $now)->delete();
            RefreshToken::where('revoked', 1)->orWhereDate('expires_at', '<', $now)->delete();
        } elseif ($options['revoked']) {
            Token::where('revoked', 1)->delete();
            AuthCode::where('revoked', 1)->delete();
            RefreshToken::where('revoked', 1)->delete();
        } elseif ($options['expired']) {
            Token::whereDate('expires_at', '<', $now)->delete();
            AuthCode::whereDate('expires_at', '<', $now)->delete();
            RefreshToken::whereDate('expires_at', '<', $now)->delete();
        }
    }
}
