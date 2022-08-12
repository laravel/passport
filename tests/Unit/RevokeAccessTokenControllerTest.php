<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\RevokeAccessTokenController;
use Laravel\Passport\RefreshTokenRepository;
use Laravel\Passport\Token;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class RevokeAccessTokenControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_current_token_can_be_revoked2()
    {
        $request = Request::create('/', 'GET');

        $request->setUserResolver(function () {
            $user = m::mock();
            $user->shouldReceive('token')->once()->andReturn($token = m::mock(Token::class.'[revoke]'));

            $token->shouldReceive('revoke')->once();

            return $user;
        });

        $refreshTokenRepository = m::mock(RefreshTokenRepository::class);
        $refreshTokenRepository->shouldReceive('revokeRefreshTokensByAccessTokenId')->once();

        $controller = new RevokeAccessTokenController($refreshTokenRepository);

        $response = $controller->revokeToken($request);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->status());
    }
}
