<?php

namespace Laravel\Passport\Console;

use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
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
                            {--hours=168 : The number of hours to retain expired tokens and unused clients}
                            {--clients : Also purge public clients without an owner that have never been used}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge revoked and / or expired tokens and authentication codes';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $revoked = $this->option('revoked') || ! $this->option('expired');

        $expired = $this->option('expired') || ! $this->option('revoked')
            ? Date::now()->subHours($this->option('hours'))
            : false;

        $clients = $this->option('clients')
            ? Date::now()->subHours($this->option('hours'))
            : false;

        $now = Date::now();

        if ($clients) {
            $this->purgeUnusedClients($clients);
        }

        $constraint = fn (Builder $query): Builder => $query
            ->when($revoked, fn () => $query->orWhere('revoked', true))
            ->when($expired, fn () => $query->orWhere('expires_at', '<', $expired));

        Passport::token()->newQuery()
            ->where($constraint)
            ->whereDoesntHave('refreshToken', fn (Builder $query): Builder => $query
                ->where('revoked', false)
                ->where(fn (Builder $query): Builder => $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now)))
            ->delete();

        Passport::authCode()->newQuery()->where($constraint)->delete();
        Passport::refreshToken()->newQuery()->where($constraint)->delete();

        if (Passport::$deviceCodeGrantEnabled) {
            Passport::deviceCode()->newQuery()->where($constraint)->delete();
        }

        $this->components->info(sprintf('Purged %s.', Arr::join(array_filter([
            $revoked ? 'revoked items' : null,
            $expired ? "items expired for more than {$expired->longAbsoluteDiffForHumans()}" : null,
            $clients ? "unused clients created more than {$clients->longAbsoluteDiffForHumans()} ago" : null,
        ]), ', ', ' and ')));
    }

    /**
     * Purge the unused public clients created before the given date.
     */
    protected function purgeUnusedClients(CarbonInterface $before): void
    {
        $client = Passport::client();
        $columns = $client->getConnection()->getSchemaBuilder()->getColumnListing($client->getTable());

        $usage = fn (Model $model): Builder => $model->newQuery()
            ->whereColumn($model->qualifyColumn('client_id'), $client->getQualifiedKeyName());

        $client->newQuery()
            ->whereNull('secret')
            ->where('revoked', false)
            ->where('created_at', '<', $before)
            ->when(in_array('owner_id', $columns), fn (Builder $query): Builder => $query->whereNull('owner_id'))
            ->when(in_array('user_id', $columns), fn (Builder $query): Builder => $query->whereNull('user_id'))
            ->when(in_array('grant_types', $columns), fn (Builder $query): Builder => $query
                ->whereJsonDoesntContain('grant_types', 'personal_access')
                ->whereJsonDoesntContain('grant_types', 'password')
                ->whereJsonDoesntContain('grant_types', 'client_credentials'))
            ->when(in_array('personal_access_client', $columns), fn (Builder $query): Builder => $query
                ->where('personal_access_client', false))
            ->when(in_array('password_client', $columns), fn (Builder $query): Builder => $query
                ->where('password_client', false))
            ->whereNotExists($usage(Passport::token()))
            ->whereNotExists($usage(Passport::authCode()))
            ->when(Passport::$deviceCodeGrantEnabled, fn (Builder $query): Builder => $query
                ->whereNotExists($usage(Passport::deviceCode())))
            ->delete();
    }
}
