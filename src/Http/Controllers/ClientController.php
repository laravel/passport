<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Http\Rules\UriRule;

class ClientController
{
    /**
     * The client repository instance.
     *
     * @var \Laravel\Passport\ClientRepository
     */
    protected $clients;

    /**
     * The validation factory implementation.
     *
     * @var \Illuminate\Contracts\Validation\Factory
     */
    protected $validation;

    /**
     * Create a client controller instance.
     *
     * @param  \Laravel\Passport\ClientRepository  $clients
     * @param  \Illuminate\Contracts\Validation\Factory  $validation
     * @return void
     */
    public function __construct(
        ClientRepository $clients,
        ValidationFactory $validation,
    ) {
        $this->clients = $clients;
        $this->validation = $validation;
    }

    /**
     * Get all of the clients for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function forUser(Request $request)
    {
        $userId = $request->user()->getAuthIdentifier();

        $clients = $this->clients->activeForUser($userId);

        return $clients;
    }

    /**
     * Store a new client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Laravel\Passport\Client|array
     */
    public function store(Request $request)
    {
        $this->validation->make($request->all(), [
            'name' => 'required|max:255',
            'redirect_uris' => 'required|array|min:1',
            'redirect_uris.*' => ['required', new UriRule],
            'confidential' => 'boolean',
        ])->validate();

        $client = $this->clients->createAuthorizationCodeGrantClient(
            $request->name,
            $request->redirect_uris,
            (bool) $request->input('confidential', true),
            $request->user()->getAuthIdentifier(),
        );

        $client->secret = $client->plainSecret;

        return $client->makeVisible('secret');
    }

    /**
     * Update the given client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $clientId
     * @return \Illuminate\Http\Response|\Laravel\Passport\Client
     */
    public function update(Request $request, $clientId)
    {
        $client = $this->clients->findForUser($clientId, $request->user()->getAuthIdentifier());

        if (! $client) {
            return new Response('', 404);
        }

        $this->validation->make($request->all(), [
            'name' => 'required|max:255',
            'redirect_uris' => 'required|array|min:1',
            'redirect_uris.*' => ['required', new UriRule],
        ])->validate();

        return $this->clients->update(
            $client, $request->name, $request->redirect_uris
        );
    }

    /**
     * Delete the given client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $clientId
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $clientId)
    {
        $client = $this->clients->findForUser($clientId, $request->user()->getAuthIdentifier());

        if (! $client) {
            return new Response('', 404);
        }

        $this->clients->delete($client);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
