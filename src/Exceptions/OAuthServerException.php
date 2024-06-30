<?php

namespace Laravel\Passport\Exceptions;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use League\OAuth2\Server\Exception\OAuthServerException as LeagueException;

class OAuthServerException extends HttpResponseException
{
    /**
     * Create a new OAuthServerException.
     *
     * @param  \League\OAuth2\Server\Exception\OAuthServerException  $e
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function __construct(LeagueException $e, Response $response)
    {
        parent::__construct($response);
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function render($request)
    {
        return null;
    }

    /**
     * Get the HTTP response status code.
     *
     * @return int
     */
    public function statusCode()
    {
        return $this->response->getStatusCode();
    }
}
