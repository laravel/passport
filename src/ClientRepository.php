<?php

namespace Laravel\Passport;

class ClientRepository
{
    use RepositoryTrait;

    /**
     * Model instance
     *
     * @var \Illuminate\Database\Eloquent\Model $model
     */
    protected $model;

    /**
     * ClientRepository constructor.
     */
    public function __construct()
    {
        $this->model = config('passport.clients.model');
    }

    /**
     * Get a client by the given ID.
     *
     * @param  int  $id
     * @return Client|null
     */
    public function find($id)
    {
        return $this->modelInstance()->find($id);
    }

    /**
     * Get an active client by the given ID.
     *
     * @param  int  $id
     * @return Client|null
     */
    public function findActive($id)
    {
        $client = $this->find($id);

        return $client && ! $client->revoked ? $client : null;
    }

    /**
     * Get the client instances for the given user ID.
     *
     * @param  mixed  $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function forUser($userId)
    {
        return  $this->modelInstance()
                ->  where('user_id', $userId)
                ->  orderBy('name', 'asc')
                ->  get();
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
     * Get the personal access token client for the application.
     *
     * @return Client
     */
    public function personalAccessClient()
    {
        if (Passport::$personalAccessClient) {
            return $this->modelInstance()->find(Passport::$personalAccessClient);
        } else {
            return $this->makeModel(config('passport.clients.personal_access_client'))->orderBy('id', 'desc')->first()->client;
        }
    }

    /**
     * Store a new client.
     *
     * @param  int  $userId
     * @param  string  $name
     * @param  string  $redirect
     * @param  bool  $personalAccess
     * @param  bool  $password
     * @return Client
     */
    public function create($userId, $name, $redirect, $personalAccess = false, $password = false)
    {
        $client = $this->modelInstance()->forceFill([
            'user_id' => $userId,
            'name' => $name,
            'secret' => str_random(40),
            'redirect' => $redirect,
            'personal_access_client' => $personalAccess,
            'password_client' => $password,
            'revoked' => false,
        ]);

        $client->save();

        return $client;
    }

    /**
     * Store a new personal access token client.
     *
     * @param  int  $userId
     * @param  string  $name
     * @param  string  $redirect
     * @return Client
     */
    public function createPersonalAccessClient($userId, $name, $redirect)
    {
        return $this->create($userId, $name, $redirect, true);
    }

    /**
     * Store a new password grant client.
     *
     * @param  int  $userId
     * @param  string  $name
     * @param  string  $redirect
     * @return Client
     */
    public function createPasswordGrantClient($userId, $name, $redirect)
    {
        return $this->create($userId, $name, $redirect, false, true);
    }

    /**
     * Update the given client.
     *
     * @param  Client  $client
     * @param  string  $name
     * @param  string  $redirect
     * @return Client
     */
    public function update(Client $client, $name, $redirect)
    {
        $client->forceFill([
            'name' => $name, 'redirect' => $redirect,
        ])->save();

        return $client;
    }

    /**
     * Regenerate the client secret.
     *
     * @param  Client  $client
     * @return Client
     */
    public function regenerateSecret(Client $client)
    {
        $client->forceFill([
            'secret' => str_random(40),
        ])->save();

        return $client;
    }

    /**
     * Determine if the given client is revoked.
     *
     * @param  int  $id
     * @return bool
     */
    public function revoked($id)
    {
        return $this->modelInstance()
                ->where('id', $id)
                ->where('revoked', true)->exists();
    }

    /**
     * Delete the given client.
     *
     * @param  Client  $client
     * @return void
     */
    public function delete(Client $client)
    {
        $client->tokens()->update(['revoked' => true]);

        $client->forceFill(['revoked' => true])->save();
    }
}
