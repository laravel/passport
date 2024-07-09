<?php

namespace Laravel\Passport\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Laravel\Passport\Passport;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'passport:purge')]
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
                            {--hours=168 : The number of hours to retain expired tokens}';

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
        $revoked = $this->option('revoked') || ! $this->option('expired');
        $expired = $this->option('expired') || ! $this->option('revoked')
            ? Carbon::now()->subHours($this->option('hours'))
            : false;

        Passport::token()->where(function (Builder $query) use ($revoked, $expired) {
            $query->when($revoked, function ($query) {
                $query->orWhere('revoked', true);
            });

            $query->when($expired, function ($query, $expired) {
                $query->orWhere('expires_at', '<', $expired);
            });
        })->delete();

        Passport::authCode()->where(function (Builder $query) use ($revoked, $expired) {
            $query->when($revoked, function ($query) {
                $query->orWhere('revoked', true);
            });

            $query->when($expired, function ($query, $expired) {
                $query->orWhere('expires_at', '<', $expired);
            });
        })->delete();

        Passport::refreshToken()->where(function (Builder $query) use ($revoked, $expired) {
            $query->when($revoked, function ($query) {
                $query->orWhere('revoked', true);
            });

            $query->when($expired, function ($query, $expired) {
                $query->orWhere('expires_at', '<', $expired);
            });
        })->delete();

        $this->components->info(sprintf('Purged %s.', implode(' and ', array_filter([
            $revoked ? 'revoked items' : null,
            $expired ? "items expired {$expired->diffForHumans()}" : null,
        ]))));
    }
}
