<?php

namespace Laravel\Passport\Http\Controllers;

use Laravel\Passport\Passport;

/**
 * @deprecated Will be removed in a future Laravel version.
 */
class ScopeController
{
    /**
     * Get all of the available scopes for the application.
     *
     * @return \Illuminate\Support\Collection
     */
    public function all()
    {
        return Passport::scopes();
    }
}
