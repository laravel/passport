<?php

namespace Laravel\Passport\Tests;

use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Laravel\Passport\Http\Controllers\AuthorizedAccessTokenController;
use Laravel\Passport\RefreshTokenRepository;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class AuthorizedAccessTokenControllerTest extends TestCase
{
    /**
     * @var \Mockery\Mock|\Laravel\Passport\TokenRepository
     */
    protected $tokenRepository;

    /**
     * @var \Mockery\Mock|\Laravel\Passport\RefreshTokenRepository
     */
    protected $refreshTokenRepository;

    /**
     * @var AuthorizedAccessTokenController
     */
    protected $controller;

    protected function setUp(): void
    {
        $this->tokenRepository = m::mock(TokenRepository::class);
        $this->refreshTokenRepository = m::mock(RefreshTokenRepository::class);
        $this->controller = new AuthorizedAccessTokenController($this->tokenRepository, $this->refreshTokenRepository);
    }

    protected function tearDown(): void
    {
        m::close();

        unset($this->tokenRepository, $this->controller);
    }

    public function test_tokens_can_be_retrieved_for_users()
    {
        $request = Request::create('/', 'GET');

        $token1 = new Token;
        $token2 = new Token;

        $userTokens = m::mock();
        $client1 = new Client;
        $client1->personal_access_client = true;
        $client2 = new Client;
        $client2->personal_access_client = false;
        $token1->client = $client1;
        $token2->client = $client2;
        $userTokens->shouldReceive('load')->with('client')->andReturn(collect([
            $token1, $token2,
        ]));

        $this->tokenRepository->shouldReceive('forUser')->andReturn($userTokens);

        $request->setUserResolver(function () use ($token1, $token2) {
            $user = m::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $tokens = $this->controller->forUser($request);

        $this->assertCount(1, $tokens);
        $this->assertEquals($token2, $tokens[0]);
    }

    public function test_tokens_can_be_deleted()
    {
        $request = Request::create('/', 'GET');

        $token1 = m::mock(Token::class.'[revoke]');
        $token1->id = 1;
        $token1->shouldReceive('revoke')->once();

        $this->tokenRepository->shouldReceive('findForUser')->andReturn($token1);
        $this->refreshTokenRepository->shouldReceive('revokeRefreshTokensByAccessTokenId')->once();

        $request->setUserResolver(function () {
            $user = m::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $response = $this->controller->destroy($request, 1);

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->status());
    }

    public function test_not_found_response_is_returned_if_user_doesnt_have_token()
    {
        $request = Request::create('/', 'GET');

        $this->tokenRepository->shouldReceive('findForUser')->with(3, 1)->andReturnNull();

        $request->setUserResolver(function () {
            $user = m::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $this->assertEquals(404, $this->controller->destroy($request, 3)->status());
    }
}
