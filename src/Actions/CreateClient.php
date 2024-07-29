<?php

namespace Laravel\Passport\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Contracts\CreatesClients;

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
            'redirect_uri' => ['required_without:redirect_uris', 'string', 'url'],
            'redirect_uris' => ['required_without:redirect_uri', 'list'],
            'redirect_uris.*' => ['required', 'string', 'url'],
            'confidential' => 'boolean',
        ])->validateWithBag('createClient');

        return $this->clients->create(
            Auth::user()->getAuthIdentifier(),
            $input['name'],
            isset($input['redirect_uris'])
                ? implode(',', $input['redirect_uris'])
                : $input['redirect_uri'],
            null,
            false,
            false,
            (bool) ($input['confidential'] ?? false)
        );
    }
}
