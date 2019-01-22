<?php

namespace Laravel\Passport\Tests;

use Mockery as m;
use Laravel\Passport\Token;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\TokenRepository;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Passport\Http\Controllers\AuthorizedAccessTokenController;

class AuthorizedAccessTokenControllerTest extends TestCase
{
    /**
     * @var \Mockery\Mock|\Laravel\Passport\TokenRepository
     */
    protected $tokenRepository;

    /**
     * @var AuthorizedAccessTokenController
     */
    protected $controller;

    public function setUp()
    {
        $this->tokenRepository = m::mock(TokenRepository::class);
        $this->controller = new AuthorizedAccessTokenController($this->tokenRepository);
    }

    public function tearDown()
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
