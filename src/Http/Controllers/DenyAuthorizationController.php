<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Contracts\Routing\ResponseFactory;

class DenyAuthorizationController
{
    use RetrievesAuthRequestFromSession;

    /**
     * The response factory implementation.
     *
     * @var \Illuminate\Contracts\Routing\ResponseFactory
     */
    protected $response;

    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Contracts\Routing\ResponseFactory  $response
     * @return void
     */
    public function __construct(ResponseFactory $response)
    {
        $this->response = $response;
    }

    /**
     * Deny the authorization request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deny(Request $request)
    {
        $authRequest = $this->getAuthRequestFromSession($request);

        $uri = $authRequest->getRedirectUri();
        $client_uris = $authRequest->getClient()->getRedirectUri();
        $client_uris = is_array($client_uris) ? $client_uris : array($client_uris);

        if (!in_array($uri, $client_uris)) {
            $uri = Arr::first($client_uris);
        }

        $separator = $authRequest->getGrantTypeId() === 'implicit' ? '#' : '?';

        return $this->response->redirectTo(
            $uri.$separator.'error=access_denied&state='.$request->input('state')
        );
    }
}
