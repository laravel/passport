<?php

namespace Laravel\Passport\Contracts;

use Laravel\Passport\Client;

interface UpdatesClients
{
    /**
     * Validate and update the given client.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(Client $client, array $input): void;
}
