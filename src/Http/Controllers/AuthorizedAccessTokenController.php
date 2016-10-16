<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthorizedAccessTokenController
{
    /**
     * Get all of the authorized tokens for the authenticated user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function forUser(Request $request)
    {
        return $request->user()->tokens->load('client')->filter(function ($token) {
            return ! $token->client->firstParty() && ! $token->revoked;
        })->values();
    }

    /**
     * Delete the given token.
     *
     * @param  Request  $request
     * @param  string  $tokenId
     * @return Response
     */
    public function destroy(Request $request, $tokenId)
    {
        if (is_null($token = $request->user()->tokens->find($tokenId))) {
            return new Response('', 404);
        }

        $token->revoke();
    }
}
