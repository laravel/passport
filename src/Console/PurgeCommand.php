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
    protected $signature = 'passport:purge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purges revoked and expired tokens from oauth_access_tokens, oauth_auth_codes and oauth_refresh_tokens';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        Token::where('revoked', 1)->orWhereDate('expires_at', '<', $now)->delete();
        AuthCode::where('revoked', 1)->orWhereDate('expires_at', '<', $now)->delete();
        RefreshToken::where('revoked', 1)->orWhereDate('expires_at', '<', $now)->delete();
    }
}
