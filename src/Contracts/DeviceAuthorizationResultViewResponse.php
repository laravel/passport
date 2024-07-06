<?php

namespace Laravel\Passport\Contracts;

use Illuminate\Contracts\Support\Responsable;

interface DeviceAuthorizationResultViewResponse extends Responsable
{
    /**
     * Specify the parameters that should be passed to the view.
     *
     * @param  array  $parameters
     * @return $this
     */
    public function withParameters($parameters = []);
}
