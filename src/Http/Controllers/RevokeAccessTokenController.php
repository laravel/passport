<?php

namespace Laravel\Passport\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\RefreshTokenRepository;

class RevokeAccessTokenController
{
    /**
     * The refresh token repository implementation.
     *
     * @var \Laravel\Passport\RefreshTokenRepository
     */
    protected $refreshTokenRepository;

    /**
     * Create a new controller instance.
     *
     * @param  \Laravel\Passport\RefreshTokenRepository  $refreshTokenRepository
     * @return void
     */
    public function __construct(RefreshTokenRepository $refreshTokenRepository)
    {
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    /**
     * Revoke the current access token being used by the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function revokeToken(Request $request)
    {
        $token = $request->user()->token();

        $token->revoke();

        $this->refreshTokenRepository->revokeRefreshTokensByAccessTokenId($token->getKey());

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
