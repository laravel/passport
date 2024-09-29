<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Http\Response;
use Psr\Http\Message\ResponseInterface;

trait ConvertsPsrResponses
{
    /**
     * Convert a PSR7 response to a Illuminate Response.
     */
    public function convertResponse(ResponseInterface $psrResponse): Response
    {
        return new Response(
            $psrResponse->getBody(),
            $psrResponse->getStatusCode(),
            $psrResponse->getHeaders()
        );
    }
}
