<?php

namespace Laravel\Passport;

use Illuminate\Support\Str;

class ClientRepository
{
    /**
     * Get a client by the given ID.
     *
     * @param  int|string  $id
     * @return \Laravel\Passport\Client|null
     */
    public function find($id)
    {
        $client = Passport::client();

        return $client->where($client->getKeyName(), $id)->first();
    }

    /**
     * Get an active client by the given ID.
     *
     * @param  int|string  $id
     * @return \Laravel\Passport\Client|null
     */
    public function findActive($id)
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

    /**
     * Store a new client.
     *
     * @param  string[]  $redirectUris
     * @param  string[]  $grantTypes
     */
    protected function create(
        string $name,
        array $grantTypes,
        array $redirectUris = [],
        ?string $provider = null,
        ?string $userId = null,
        bool $confidential = true
    ): Client {
        $client = Passport::client()->forceFill([
            'user_id' => $userId,
            'name' => $name,
            'secret' => $confidential ? Str::random(40) : null,
            'grant_types' => $grantTypes,
            'redirect_uris' => $redirectUris,
            'provider' => $provider,
            'revoked' => false,
        ]);

        $client->save();

        return $client;
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
    public function createPasswordGrantClient(string $name, ?string $provider = null): Client
    {
        return $this->create($name, ['password', 'refresh_token'], [], $provider);
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
        return $this->create($name, ['implicit'], $redirectUris);
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
        ?string $userId = null
    ): Client {
        return $this->create(
            $name, ['authorization_code', 'refresh_token'], $redirectUris, null, $userId, $confidential
        );
    }

    /**
     * Update the given client.
     *
     * @param  string[]  $redirectUris
     */
    public function update(Client $client, string $name, array $redirectUris): bool
    {
        return $client->forceFill([
            'name' => $name,
            'redirect_uris' => $redirectUris,
        ])->save();
    }

    /**
     * Regenerate the client secret.
     *
     * @param  \Laravel\Passport\Client  $client
     * @return \Laravel\Passport\Client
     */
    public function regenerateSecret(Client $client)
    {
        $client->forceFill([
            'secret' => Str::random(40),
        ])->save();

        return $client;
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
