<?php

namespace Laravel\Passport;

class ClientRepository
{
    use RepositoryTrait;

    /**
     * The client model.
     *
     * @var string
     */
    protected static $model = Client::class;

    /**
     * The personal access client model.
     *
     * @var mixed
     */
    protected static $personalAccessClientModel = PersonalAccessClient::class;

    /**
     * Get a client by the given ID.
     *
     * @param  int  $id
     * @return Client|null
     */
    public function find($id)
    {
        return $this->createModel()->find($id);
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
        return $this->createModel()
            ->where('user_id', $userId)
            ->orderBy('name', 'desc')
            ->get();
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
            return $this->find(Passport::$personalAccessClient);
        } else {
            return $this->createPersonalAccessClientModel()->orderBy('id', 'desc')->first()->client;
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
        $client = $this->createModel()->forceFill([
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
        return $this->createModel()
            ->where('id', $id)
            ->where('revoked', true)
            ->exists();
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

    /**
     * {@inheritdoc}
     */
    public static function getModel()
    {
        return static::$model;
    }

    /**
     * {@inheritdoc}
     */
    public static function setModel($model)
    {
        static::$model = $model;

        return new static;
    }

    /**
     * Get the personal access client model.
     *
     * @return mixed
     */
    public static function getPersonalAccessClientModel()
    {
        return static::$personalAccessClientModel;
    }

    /**
     * Set the personal access client model.
     *
     * @param  mixed  $personalAccessClientModel
     * @return static
     */
    public static function setPersonalAccessClientModel($personalAccessClientModel)
    {
        static::$personalAccessClientModel = $personalAccessClientModel;

        return new static;
    }

    /**
     * Create a new personal access client model instance.
     *
     * @param  array  $attributes  Optional array of model attributes.
     * @return mixed
     */
    public function createPersonalAccessClientModel(array $attributes = [])
    {
        if (is_string($model = $this->getPersonalAccessClientModel())) {
            if (! class_exists($class = '\\'.ltrim($model, '\\'))) {
                throw new RuntimeException("Class {$model} does not exist!");
            }

            $model = new $model($attributes);
        }

        return $model;
    }
}
