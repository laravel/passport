<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\Passport;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\PersonalAccessTokenResult;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

class PersonalAccessTokenController
{
    /**
     * The token repository implementation.
     *
     * @var TokenRepository
     */
    protected $tokenRepository;

    /**
     * The validation factory implementation.
     *
     * @var ValidationFactory
     */
    protected $validation;

    /**
     * Create a controller instance.
     *
     * @param  TokenRepository  $tokenRepository
     * @param  ValidationFactory  $validation
     * @return void
     */
    public function __construct(TokenRepository $tokenRepository, ValidationFactory $validation)
    {
        $this->validation = $validation;
        $this->tokenRepository = $tokenRepository;
    }

    /**
     * Get all of the personal access tokens for the authenticated user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function forUser(Request $request)
    {
        $tokens = $this->tokenRepository->forUser($request->user()->getKey());

        return $tokens->load('client')->filter(function ($token) {
            return $token->client->personal_access_client && ! $token->revoked;
        })->values();
    }

    /**
     * Create a new personal access token for the user.
     *
     * @param  Request  $request
     * @return PersonalAccessTokenResult
     */
    public function store(Request $request)
    {
        $this->validation->make($request->all(), [
            'name' => 'required|max:255',
            'scopes' => 'array|in:'.implode(',', Passport::scopeIds()),
        ])->validate();

        return $request->user()->createToken(
            $request->name, $request->scopes ?: []
        );
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
