<?php

namespace Laravel\Passport\Http\Controllers;

use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\ClientRepository;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

class ClientController
{
    /**
     * The client repository instance.
     *
     * @var ClientRepository
     */
    protected $clients;

    /**
     * The validation factory implementation.
     *
     * @var ValidationFactory
     */
    protected $validation;

    /**
     * Create a client controller instance.
     *
     * @param  ClientRepository  $clients
     * @param  ValidationFactory  $validation
     * @return void
     */
    public function __construct(ClientRepository $clients,
                                ValidationFactory $validation)
    {
        $this->clients = $clients;
        $this->validation = $validation;

        if (!Passport::$useClientUUIDs) {
            $this->lookup = 'find';
        } else {
            $this->lookup = 'findUUID';
        }
    }

    /**
     * Get all of the clients for the authenticated user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function forUser(Request $request)
    {
        return $this->clients->activeForUser($request->user()->id)->makeVisible('secret');
    }

    /**
     * Store a new client.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $this->validation->make($request->all(), [
            'name' => 'required|max:255',
            'redirect' => 'required|url',
        ])->validate();

        return $this->clients->create(
            $request->user()->id, $request->name, $request->redirect
        )->makeVisible('secret');
    }

    /**
     * Update the given client.
     *
     * @param  Request  $request
     * @param  string  $clientId
     * @return Response
     */
    public function update(Request $request, $clientId)
    {
        if (! $request->user()->clients->{$this->lookup}($clientId)) {
            return new Response('', 404);
        }

        $this->validation->make($request->all(), [
            'name' => 'required|max:255',
            'redirect' => 'required|url',
        ])->validate();

        return $this->clients->update(
            $request->user()->clients->{$this->lookup}($clientId),
            $request->name, $request->redirect
        );
    }

    /**
     * Delete the given client.
     *
     * @param  Request  $request
     * @param  string  $clientId
     * @return Response
     */
    public function destroy(Request $request, $clientId)
    {
        if (! $request->user()->clients->{$this->lookup}($clientId)) {
            return new Response('', 404);
        }

        $this->clients->delete(
            $request->user()->clients->{$this->lookup}($clientId)
        );
    }
}
