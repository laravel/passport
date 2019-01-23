<?php

namespace Laravel\Passport\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\RefreshTokenRepository;
use Laravel\Passport\Bridge\RefreshTokenRepository as BridgeRefreshTokenRepository;

class BridgeRefreshTokenRepositoryTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_it_can_determine_if_a_refresh_token_is_revoked()
    {
        $refreshToken = new RevokedRefreshToken;
        $repository = $this->repository($refreshToken);

        $this->assertTrue($repository->isRefreshTokenRevoked('tokenId'));
    }

    public function test_a_refresh_token_is_also_revoked_if_it_cannot_be_found()
    {
        $refreshToken = null;
        $repository = $this->repository($refreshToken);

        $this->assertTrue($repository->isRefreshTokenRevoked('tokenId'));
    }

    public function test_it_can_determine_if_a_refresh_token_is_not_revoked()
    {
        $refreshToken = new ActiveRefreshToken;
        $repository = $this->repository($refreshToken);

        $this->assertFalse($repository->isRefreshTokenRevoked('tokenId'));
    }

    private function repository($refreshToken): BridgeRefreshTokenRepository
    {
        $refreshTokenRepository = m::mock(RefreshTokenRepository::class)->makePartial();
        $refreshTokenRepository->shouldReceive('find')
            ->with('tokenId')
            ->andReturn($refreshToken);

        $events = m::mock('Illuminate\Contracts\Events\Dispatcher');

        return new BridgeRefreshTokenRepository($refreshTokenRepository, $events);
    }
}

class ActiveRefreshToken
{
    public $revoked = false;
}

class RevokedRefreshToken
{
    public $revoked = true;
}
