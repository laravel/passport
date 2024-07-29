<?php

namespace Laravel\Passport\Contracts;

use Laravel\Passport\Client;

interface CreatesClients
{
    /**
     * Validate and create a newly registered client.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): Client;
}
