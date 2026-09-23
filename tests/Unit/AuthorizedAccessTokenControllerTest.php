<?php

namespace Laravel\Passport\Tests\Unit;

use JMac\Testing\Double;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Laravel\Passport\Http\Controllers\AuthorizedAccessTokenController;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class AuthorizedAccessTokenControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var \Mockery\Mock|\Laravel\Passport\TokenRepository
     */
    protected $tokenRepository;

    /**
     * @var AuthorizedAccessTokenController
     */
    protected $controller;

    protected function setUp(): void
    {
        $this->tokenRepository = Double::for(TokenRepository::class);
        $this->controller = new AuthorizedAccessTokenController($this->tokenRepository);
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

        $client1 = new Client;
        $client1->grant_types = ['personal_access'];
        $client2 = new Client;
        $client2->grant_types = [];
        $client2->user_id = 2;
        $token1->client = $client1;
        $token2->client = $client2;
        $userTokens = (new Token)->newCollection([
            $token1, $token2,
        ]);

        $this->tokenRepository->allows('forUser')->returns($userTokens);

        $request->setUserResolver(function () {
            $user = Double::for(Authenticatable::class);
            $user->allows('getAuthIdentifier')->returns(1);

            return $user;
        });

        $tokens = $this->controller->forUser($request);

        $this->assertCount(1, $tokens);
        $this->assertEquals($token2, $tokens[0]);
    }

    public function test_tokens_can_be_deleted()
    {
        $request = Request::create('/', 'GET');

        $token1 = Double::for(Token::class)->passthru();
        $token1->id = 1;
        $token1->refreshToken = Double::for(RefreshToken::class);
        $token1->refreshToken->expects('revoke');
        $token1->expects('revoke');

        $this->tokenRepository->allows('findForUser')->returns($token1);

        $request->setUserResolver(function () {
            $user = Double::for(Authenticatable::class);
            $user->allows('getAuthIdentifier')->returns(1);

            return $user;
        });

        $response = $this->controller->destroy($request, 1);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->status());
    }

    public function test_not_found_response_is_returned_if_user_doesnt_have_token()
    {
        $user = Double::for(Authenticatable::class);
        $user->allows('getAuthIdentifier')->returns(1);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->tokenRepository->allows('findForUser')->with(3, $user)->returns(null);

        $this->assertSame(404, $this->controller->destroy($request, 3)->status());
    }
}
