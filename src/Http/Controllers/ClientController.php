<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Http\Rules\RedirectRule;

/**
 * @deprecated Will be removed in a future Laravel version.
 */
class ClientController
{
    /**
     * Create a client controller instance.
     */
    public function __construct(
        protected ClientRepository $clients,
        protected ValidationFactory $validation,
        protected RedirectRule $redirectRule
    ) {
    }

    /**
     * Get all of the clients for the authenticated user.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \Laravel\Passport\Client>
     */
    public function forUser(Request $request): Collection
    {
        return $this->clients->forUser($request->user());
    }

    /**
     * Store a new client.
     */
    public function store(Request $request): Client
    {
        $this->validation->make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'redirect' => ['required', $this->redirectRule],
            'confidential' => 'boolean',
        ])->validate();

        $client = $this->clients->createAuthorizationCodeGrantClient(
            $request->name,
            explode(',', $request->redirect),
            (bool) $request->input('confidential', true),
            $request->user(),
        );

        $client->secret = $client->plainSecret;

        return $client->makeVisible('secret');
    }

    /**
     * Update the given client.
     */
    public function update(Request $request, string|int $clientId): Response|Client
    {
        $client = $this->clients->findForUser($clientId, $request->user());

        if (! $client) {
            return new Response('', 404);
        }

        $this->validation->make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'redirect' => ['required', $this->redirectRule],
        ])->validate();

        $this->clients->update(
            $client, $request->name, explode(',', $request->redirect)
        );

        return $client;
    }

    /**
     * Delete the given client.
     */
    public function destroy(Request $request, string|int $clientId): Response
    {
        $client = $this->clients->findForUser($clientId, $request->user());

        if (! $client) {
            return new Response('', 404);
        }

        $this->clients->delete($client);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
