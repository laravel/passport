<?php

namespace Laravel\Passport\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Laravel\Passport\Passport;

class PurgeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:purge
                            {--revoked : Only purge revoked tokens and authentication codes}
                            {--days=7 : Days expired tokens will be kept}
                            {--hours= : Hours expired tokens will be kept}
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
        if($this->option('hours')){
            $expired = Carbon::now()->subHours($this->option('hours'));
        } else {
            $expired = Carbon::now()->subHours($this->option('days'));
        }

        if (($this->option('revoked') && $this->option('expired')) ||
            (! $this->option('revoked') && ! $this->option('expired'))) {
            Passport::token()->where('revoked', 1)->orWhereDate('expires_at', '<', $expired)->delete();
            Passport::authCode()->where('revoked', 1)->orWhereDate('expires_at', '<', $expired)->delete();
            Passport::refreshToken()->where('revoked', 1)->orWhereDate('expires_at', '<', $expired)->delete();

            $this->info('Purged revoked items and items expired for more than seven days.');
        } elseif ($this->option('revoked')) {
            Passport::token()->where('revoked', 1)->delete();
            Passport::authCode()->where('revoked', 1)->delete();
            Passport::refreshToken()->where('revoked', 1)->delete();

            $this->info('Purged revoked items.');
        } elseif ($this->option('expired')) {
            Passport::token()->whereDate('expires_at', '<', $expired)->delete();
            Passport::authCode()->whereDate('expires_at', '<', $expired)->delete();
            Passport::refreshToken()->whereDate('expires_at', '<', $expired)->delete();

            $this->info('Purged items expired for more than seven days.');
        }
    }
}
