<?php

namespace Laravel\Passport;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use RuntimeException;

class ClientRepository
{
    /**
     * Get a client by the given ID.
     */
    public function find(string|int $id): ?Client
    {
        return once(fn () => Passport::client()->newQuery()->find($id));
    }

    /**
     * Get an active client by the given ID.
     */
    public function findActive(string|int $id): ?Client
    {
        $client = $this->find($id);

        return $client && ! $client->revoked ? $client : null;
    }

    /**
     * Get a client instance for the given ID and user ID.
     *
     * @param  int|string  $clientId
     * @param  mixed  $userId
     * @return \Laravel\Passport\Client|null
     */
    public function findForUser($clientId, $userId)
    {
        $client = Passport::client();

        return $client
                    ->where($client->getKeyName(), $clientId)
                    ->where('user_id', $userId)
                    ->first();
    }

    /**
     * Get the client instances for the given user ID.
     *
     * @param  mixed  $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function forUser($userId)
    {
        return Passport::client()
                    ->where('user_id', $userId)
                    ->orderBy('name', 'asc')->get();
    }

    /**
     * Get the active client instances for the given user ID.
     *
     * @param  mixed  $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function activeForUser($userId)
    {
        return $this->forUser($userId)->reject(function ($client) {
            return $client->revoked;
        })->values();
    }

    /*
     * Get the latest active personal access client for the given user provider.
     *
     * @throws \RuntimeException
     */
    public function personalAccessClient(string $provider): Client
    {
        return Passport::client()
            ->newQuery()
            ->where('revoked', false)
            ->whereNull('user_id')
            ->where(function (Builder $query) use ($provider) {
                $query->when($provider === config('auth.guards.api.provider'), function (Builder $query) {
                    $query->orWhereNull('provider');
                })->orWhere('provider', $provider);
            })
            ->latest()
            ->get()
            ->first(fn (Client $client) => $client->hasGrantType('personal_access'))
            ?? throw new RuntimeException(
                "Personal access client not found for '$provider' user provider. Please create one."
            );
    }

    /**
     * Store a new client.
     *
     * @param  string[]  $grantTypes
     * @param  string[]  $redirectUris
     * @param  \Laravel\Passport\HasApiTokens  $user
     */
    protected function create(
        string $name,
        array $grantTypes,
        array $redirectUris = [],
        ?string $provider = null,
        bool $confidential = true,
        ?Authenticatable $user = null
    ): Client {
        $client = Passport::client();
        $columns = $client->getConnection()->getSchemaBuilder()->getColumnListing($client->getTable());

        $attributes = [
            'name' => $name,
            'secret' => $confidential ? Str::random(40) : null,
            'provider' => $provider,
            'revoked' => false,
            ...(in_array('redirect_uris', $columns) ? [
                'redirect_uris' => $redirectUris,
            ] : [
                'redirect' => implode(',', $redirectUris),
            ]),
            ...(in_array('grant_types', $columns) ? [
                'grant_types' => $grantTypes,
            ] : [
                'personal_access_client' => in_array('personal_access', $grantTypes),
                'password_client' => in_array('password', $grantTypes),
            ]),
        ];

        return $user
            ? $user->clients()->forceCreate($attributes)
            : $client->newQuery()->forceCreate($attributes);
    }

    /**
     * Store a new personal access token client.
     */
    public function createPersonalAccessGrantClient(string $name, ?string $provider = null): Client
    {
        return $this->create($name, ['personal_access'], [], $provider);
    }

    /**
     * Store a new password grant client.
     */
    public function createPasswordGrantClient(string $name, ?string $provider = null, bool $confidential = false): Client
    {
        return $this->create($name, ['password', 'refresh_token'], [], $provider, $confidential);
    }

    /**
     * Store a new client credentials grant client.
     */
    public function createClientCredentialsGrantClient(string $name): Client
    {
        return $this->create($name, ['client_credentials']);
    }

    /**
     * Store a new implicit grant client.
     *
     * @param  string[]  $redirectUris
     */
    public function createImplicitGrantClient(string $name, array $redirectUris): Client
    {
        return $this->create($name, ['implicit'], $redirectUris, null, false);
    }

    /**
     * Store a new authorization code grant client.
     *
     * @param  string[]  $redirectUris
     */
    public function createAuthorizationCodeGrantClient(
        string $name,
        array $redirectUris,
        bool $confidential = true,
        ?Authenticatable $user = null
    ): Client {
        return $this->create(
            $name, ['authorization_code', 'refresh_token'], $redirectUris, null, $confidential, $user
        );
    }

    /**
     * Update the given client.
     *
     * @param  string[]  $redirectUris
     */
    public function update(Client $client, string $name, array $redirectUris): bool
    {
        $columns = $client->getConnection()->getSchemaBuilder()->getColumnListing($client->getTable());

        return $client->forceFill([
            'name' => $name,
            ...(in_array('redirect_uris', $columns) ? [
                'redirect_uris' => $redirectUris,
            ] : [
                'redirect' => implode(',', $redirectUris),
            ]),
        ])->save();
    }

    /**
     * Regenerate the client secret.
     */
    public function regenerateSecret(Client $client): bool
    {
        return $client->forceFill([
            'secret' => Str::random(40),
        ])->save();
    }

    /**
     * Determine if the given client is revoked.
     *
     * @param  int|string  $id
     * @return bool
     */
    public function revoked($id)
    {
        $client = $this->find($id);

        return is_null($client) || $client->revoked;
    }

    /**
     * Delete the given client.
     *
     * @param  \Laravel\Passport\Client  $client
     * @return void
     */
    public function delete(Client $client)
    {
        $client->tokens()->update(['revoked' => true]);

        $client->forceFill(['revoked' => true])->save();
    }
}
