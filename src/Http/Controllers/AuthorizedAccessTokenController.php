<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Events\Dispatcher;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\Events\AccessTokenRevoked;

class AuthorizedAccessTokenController
{
    /**
     * The token repository implementation.
     *
     * @var use Illuminate\Events\Dispatcher;
     */
    protected $events;

    /**
     * The events dispatcher.
     *
     * @var \Laravel\Passport\TokenRepository
     */
    protected $tokenRepository;

    /**
     * Create a new controller instance.
     *
     * @param  \Laravel\Passport\TokenRepository  $tokenRepository
     * @return void
     */
    public function __construct(TokenRepository $tokenRepository, Dispatcher $events)
    {
        $this->tokenRepository = $tokenRepository;
        $this->events = $events;
    }

    /**
     * Get all of the authorized tokens for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function forUser(Request $request)
    {
        $tokens = $this->tokenRepository->forUser($request->user()->getKey());

        return $tokens->load('client')->filter(function ($token) {
            return ! $token->client->firstParty() && ! $token->revoked;
        })->values();
    }

    /**
     * Delete the given token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $tokenId
     * @return \Illuminate\Http\Response
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

        $this->events->dispatch(new AccessTokenRevoked(
            $token->id,
            $token->user_id,
            $token->client_id
        )
    );

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
