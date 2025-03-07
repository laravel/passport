<?php

namespace Laravel\Passport;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
     * @deprecated Use $user->oauthApps()->find()
     *
     * @param  \Laravel\Passport\Contracts\OAuthenticatable  $user
     */
    public function findForUser(string|int $clientId, Authenticatable $user): ?Client
    {
        return $user->clients()->where('revoked', false)->find($clientId);
    }

    /**
     * Get the client instances for the given user ID.
     *
     * @deprecated Use $user->oauthApps()
     *
     * @param  \Laravel\Passport\Contracts\OAuthenticatable  $user
     * @return \Illuminate\Database\Eloquent\Collection<int, \Laravel\Passport\Client>
     */
    public function forUser(Authenticatable $user): Collection
    {
        return $user->clients()->where('revoked', false)->orderBy('name')->get();
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
            ->where(function (Builder $query) use ($provider): void {
                $query->when($provider === config('auth.guards.api.provider'), function (Builder $query): void {
                    $query->orWhereNull('provider');
                })->orWhere('provider', $provider);
            })
            ->latest()
            ->get()
            ->first(fn (Client $client): bool => $client->hasGrantType('personal_access'))
            ?? throw new RuntimeException(
                "Personal access client not found for '$provider' user provider. Please create one."
            );
    }

    /**
     * Store a new client.
     *
     * @param  string[]  $grantTypes
     * @param  string[]  $redirectUris
     * @param  \Laravel\Passport\Contracts\OAuthenticatable|null  $user
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

        return match (true) {
            ! is_null($user) && in_array('user_id', $columns) => $user->clients()->forceCreate($attributes),
            ! is_null($user) => $user->oauthApps()->forceCreate($attributes),
            default => $client->newQuery()->forceCreate($attributes),
        };
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
     * Store a new device authorization grant client.
     *
     * @param  \Laravel\Passport\Contracts\OAuthenticatable|null  $user
     */
    public function createDeviceAuthorizationGrantClient(
        string $name,
        bool $confidential = true,
        ?Authenticatable $user = null
    ): Client {
        return $this->create(
            $name, ['urn:ietf:params:oauth:grant-type:device_code', 'refresh_token'], [], null, $confidential, $user
        );
    }

    /**
     * Store a new authorization code grant client.
     *
     * @param  string[]  $redirectUris
     * @param  \Laravel\Passport\Contracts\OAuthenticatable|null  $user
     */
    public function createAuthorizationCodeGrantClient(
        string $name,
        array $redirectUris,
        bool $confidential = true,
        ?Authenticatable $user = null,
        bool $enableDeviceFlow = false
    ): Client {
        $grantTypes = ['authorization_code', 'refresh_token'];

        if ($enableDeviceFlow) {
            $grantTypes[] = 'urn:ietf:params:oauth:grant-type:device_code';
        }

        return $this->create($name, $grantTypes, $redirectUris, null, $confidential, $user);
    }

    /**
     * Update the given client.
     *
     * @deprecated Will be removed in a future Laravel version.
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
     * Revoke the given client and its tokens.
     *
     * @deprecated Will be removed in a future Laravel version.
     */
    public function delete(Client $client): void
    {
        $client->tokens()->with('refreshToken')->each(function (Token $token): void {
            $token->refreshToken?->revoke();
            $token->revoke();
        });

        $client->forceFill(['revoked' => true])->save();
    }
}
