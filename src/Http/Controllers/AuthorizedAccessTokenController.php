<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;

/**
 * @deprecated Will be removed in a future Laravel version.
 */
class AuthorizedAccessTokenController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected TokenRepository $tokenRepository
    ) {
    }

    /**
     * Get all of the authorized tokens for the authenticated user.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \Laravel\Passport\Token>
     */
    public function forUser(Request $request): Collection
    {
        return $this->tokenRepository->forUser($request->user())
            ->reject(fn (Token $token): bool => $token->client->revoked || $token->client->firstParty())
            ->values();
    }

    /**
     * Delete the given token.
     */
    public function destroy(Request $request, string $tokenId): Response
    {
        $token = $this->tokenRepository->findForUser(
            $tokenId, $request->user()
        );

        if (is_null($token)) {
            return new Response('', 404);
        }

        $token->revoke();
        $token->refreshToken?->revoke();

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
