<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\Routing\ResponseFactory;

class DenyAuthorizationController
{
    use RetrievesAuthRequestFromSession;

    /**
     * The response factory implementation.
     *
     * @var ResponseFactory
     */
    protected $response;

    /**
     * Create a new controller instance.
     *
     * @param  ResponseFactory  $response
     * @return void
     */
    public function __construct(ResponseFactory $response)
    {
        $this->response = $response;
    }

    /**
     * Deny the authorization request.
     *
     * @param  Request  $request
     * @return Response
     */
    public function deny(Request $request)
    {
        $redirect = $this->getAuthRequestFromSession($request)
                    ->getClient()->getRedirectUri();

        return $this->response->redirectTo(
            $redirect.'?error=access_denied&state='.$request->input('state')
        );
    }
}
