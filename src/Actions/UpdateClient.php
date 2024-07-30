<?php

namespace Laravel\Passport\Actions;

use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Contracts\UpdatesClients;
use Laravel\Passport\Http\Rules\UriRule;

class UpdateClient implements UpdatesClients
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
    public function update(Client $client, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'redirect_uri' => ['required_without:redirect_uris', 'string', new UriRule],
            'redirect_uris' => ['required_without:redirect_uri', 'list'],
            'redirect_uris.*' => ['required', 'string', new UriRule],
        ])->validateWithBag('updateClient');

        $this->clients->update(
            $client,
            $input['name'],
            isset($input['redirect_uris'])
                ? implode(',', $input['redirect_uris'])
                : $input['redirect_uri'],
        );
    }
}
