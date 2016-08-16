<?php

namespace Laravel\Passport\Http\Controllers;

use Laravel\Passport\Passport;
use Illuminate\Routing\Controller;

class ScopeController extends Controller
{
    /**
     * Get all of the available scopes for the application.
     *
     * @return Response
     */
    public function all()
    {
        return Passport::scopes();
    }
}
