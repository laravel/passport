<?php

namespace Laravel\Passport\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use League\OAuth2\Server\Exception\OAuthServerException as LeagueException;

class OAuthServerException extends HttpResponseException
{
    /**
     * Create a new OAuthServerException.
     */
    public function __construct(LeagueException $e, Response $response)
    {
        parent::__construct($response, $e);
    }
}
