<?php

namespace Laravel\Passport\Tests;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class BridgeRefreshTokenRepositoryTest extends TestCase
{
    protected function tearDown(): void
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

    private function repository($refreshToken): RefreshTokenRepository
    {
        $queryBuilder = m::mock(Builder::class);
        $queryBuilder->shouldReceive('first')->andReturn($refreshToken);
        $queryBuilder->shouldReceive('where')->andReturn($queryBuilder);

        $connection = m::mock(Connection::class);
        $connection->shouldReceive('table')->andReturn($queryBuilder);

        $events = m::mock(Dispatcher::class);

        return new RefreshTokenRepository($connection, $events);
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
