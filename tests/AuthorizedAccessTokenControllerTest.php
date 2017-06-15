<?php

use Mockery\Mock;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\Http\Controllers\AuthorizedAccessTokenController;

class AuthorizedAccessTokenControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Mock|TokenRepository
     */
    protected $tokenRepository;

    /**
     * @var AuthorizedAccessTokenController
     */
    protected $controller;

    public function tearDown()
    {
        Mockery::close();
    }

    public function setUp()
    {
        $this->tokenRepository = Mockery::mock(TokenRepository::class);
        $this->controller = new Laravel\Passport\Http\Controllers\AuthorizedAccessTokenController(
            $this->tokenRepository
        );
    }

    public function test_tokens_can_be_retrieved_for_users()
    {
        $request = Request::create('/', 'GET');

        $token1 = new Laravel\Passport\Token;
        $token2 = new Laravel\Passport\Token;

        $userTokens = Mockery::mock();
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
            $user = Mockery::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $tokens = $this->controller->forUser($request);

        $this->assertEquals(1, count($tokens));
        $this->assertEquals($token2, $tokens[0]);
    }

    public function test_tokens_can_be_deleted()
    {
        $request = Request::create('/', 'GET');

        $token1 = Mockery::mock(Laravel\Passport\Token::class.'[revoke]');
        $token1->id = 1;
        $token1->shouldReceive('revoke')->once();

        $this->tokenRepository->shouldReceive('findForUser')->andReturn($token1);

        $request->setUserResolver(function () {
            $user = Mockery::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $this->controller->destroy($request, 1);
    }

    public function test_not_found_response_is_returned_if_user_doesnt_have_token()
    {
        $request = Request::create('/', 'GET');

        $this->tokenRepository->shouldReceive('findForUser')->with(3, 1)->andReturnNull();

        $request->setUserResolver(function () {
            $user = Mockery::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $this->assertEquals(404, $this->controller->destroy($request, 3)->status());
    }
}
