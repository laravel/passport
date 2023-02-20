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
                            {--expired : Only purge expired tokens and authentication codes}
                            {--hours= : The number of hours to retain expired tokens}';

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
        $expired = $this->option('hours')
            ? Carbon::now()->subHours($this->option('hours'))
            : Carbon::now()->subDays(7);

        if (($this->option('revoked') && $this->option('expired')) ||
            (! $this->option('revoked') && ! $this->option('expired'))) {
            Passport::token()->where('revoked', 1)->orWhereDate('expires_at', '<', $expired)->delete();
            Passport::authCode()->where('revoked', 1)->orWhereDate('expires_at', '<', $expired)->delete();
            Passport::refreshToken()->where('revoked', 1)->orWhereDate('expires_at', '<', $expired)->delete();

            $this->option('hours')
                ? $this->info('Purged revoked items and items expired for more than '.$this->option('hours').' hours.')
                : $this->info('Purged revoked items and items expired for more than seven days.');
        } elseif ($this->option('revoked')) {
            Passport::token()->where('revoked', 1)->delete();
            Passport::authCode()->where('revoked', 1)->delete();
            Passport::refreshToken()->where('revoked', 1)->delete();

            $this->info('Purged revoked items.');
        } elseif ($this->option('expired')) {
            Passport::token()->whereDate('expires_at', '<', $expired)->delete();
            Passport::authCode()->whereDate('expires_at', '<', $expired)->delete();
            Passport::refreshToken()->whereDate('expires_at', '<', $expired)->delete();

            $this->option('hours')
                ? $this->info('Purged items expired for more than '.$this->option('hours').' hours.')
                : $this->info('Purged items expired for more than seven days.');
        }
    }
}
