<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Laravel\Passport\Passport;
use Laravel\Passport\PersonalAccessTokenResult;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;

/**
 * @deprecated Will be removed in a future Laravel version.
 */
class PersonalAccessTokenController
{
    /**
     * Create a controller instance.
     */
    public function __construct(
        protected TokenRepository $tokenRepository,
        protected ValidationFactory $validation
    ) {
    }

    /**
     * Get all of the personal access tokens for the authenticated user.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \Laravel\Passport\Token>
     */
    public function forUser(Request $request)
    {
        return $this->tokenRepository->forUser($request->user())
            ->filter(
                fn (Token $token): bool => ! $token->client->revoked && $token->client->hasGrantType('personal_access')
            )
            ->values();
    }

    /**
     * Create a new personal access token for the user.
     */
    public function store(Request $request): PersonalAccessTokenResult
    {
        $this->validation->make($request->all(), [
            'name' => ['required', 'max:255'],
            'scopes' => ['array', Rule::in(Passport::scopeIds())],
        ])->validate();

        return $request->user()->createToken(
            $request->name, $request->scopes ?: []
        );
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

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
