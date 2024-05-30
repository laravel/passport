<?php

namespace Laravel\Passport\Http\Responses;

use Laravel\Passport\Contracts\AuthorizationViewResponse as AuthorizationViewResponseContract;

class AuthorizationViewResponse implements AuthorizationViewResponseContract
{
    use ViewResponsable;
}
