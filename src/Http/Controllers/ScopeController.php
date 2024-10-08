<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Support\Collection;
use Laravel\Passport\Passport;

/**
 * @deprecated Will be removed in a future Laravel version.
 */
class ScopeController
{
    /**
     * Get all of the available scopes for the application.
     *
     * @return \Illuminate\Support\Collection<int, \Laravel\Passport\Scope>
     */
    public function all(): Collection
    {
        return Passport::scopes();
    }
}
