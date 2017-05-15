<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\TokenRepository;

class AuthorizedAccessTokenController
{
    /**
     * The token repository implementation.
     *
     * @var TokenRepository
     */
    protected $tokenRepository;

    /**
     * Create a new controller instance.
     *
     * @param TokenRepository $tokenRepository
     * @return void
     */
    public function __construct(TokenRepository $tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;
    }

    /**
     * Get all of the authorized tokens for the authenticated user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function forUser(Request $request)
    {
        $tokens =  $this->tokenRepository->forUser($request->user()->getKey());

        return $tokens->load('client')->filter(function ($token) {
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
        $token = $this->tokenRepository->findForUser(
            $tokenId, $request->user()->getKey()
        );

        if (is_null($token)) {
            return new Response('', 404);
        }

        $token->revoke();
    }
}
