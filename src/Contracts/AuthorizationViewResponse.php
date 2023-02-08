<?php

namespace Laravel\Passport\Contracts;

use Illuminate\Contracts\Support\Responsable;

interface AuthorizationViewResponse extends Responsable
{
    /**
     * @param $parameters
     * @return mixed
     */
    public function withParameters($parameters = []);
}
