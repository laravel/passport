<?php

namespace Laravel\Passport\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Contracts\CreatesClients;
use Laravel\Passport\Http\Rules\UriRule;

class CreateClient implements CreatesClients
{
    /**
     * Create a new action instance.
     */
    public function __construct(protected ClientRepository $clients)
    {
    }

    /**
     * Validate and create a new client.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): Client
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'redirect_uris' => ['required', 'list'],
            'redirect_uris.*' => ['required', 'string', new UriRule],
            'confidential' => 'boolean',
        ])->validateWithBag('createClient');

        return $this->clients->createAuthorizationCodeGrantClient(
            $input['name'],
            $input['redirect_uris'],
            (bool) ($input['confidential'] ?? false),
            Auth::user()
        );
    }
}
