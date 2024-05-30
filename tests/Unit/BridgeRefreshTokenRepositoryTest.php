<?php

namespace Laravel\Passport\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Passport\Bridge\AccessToken;
use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\RefreshToken;
use Laravel\Passport\Bridge\RefreshTokenRepository as BridgeRefreshTokenRepository;
use Laravel\Passport\RefreshTokenRepository;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class BridgeRefreshTokenRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_access_tokens_can_be_persisted()
    {
        $expiration = CarbonImmutable::now();

        $refreshTokenRepository = m::mock(RefreshTokenRepository::class);
        $events = m::mock(Dispatcher::class);

        $refreshTokenRepository->shouldReceive('create')->once()->andReturnUsing(function ($array) use ($expiration) {
            $this->assertEquals('1', $array['id']);
            $this->assertEquals('2', $array['access_token_id']);
            $this->assertFalse($array['revoked']);
            $this->assertEquals($expiration, $array['expires_at']);
        });

        $events->shouldReceive('dispatch')->once();

        $accessToken = new AccessToken('3', [], m::mock(Client::class));
        $accessToken->setIdentifier('2');

        $refreshToken = new RefreshToken;
        $refreshToken->setIdentifier('1');
        $refreshToken->setExpiryDateTime($expiration);
        $refreshToken->setAccessToken($accessToken);

        $repository = new BridgeRefreshTokenRepository($refreshTokenRepository, $events);

        $repository->persistNewRefreshToken($refreshToken);
    }

    public function test_can_get_new_refresh_token()
    {
        $refreshTokenRepository = m::mock(RefreshTokenRepository::class);
        $events = m::mock(Dispatcher::class);
        $repository = new BridgeRefreshTokenRepository($refreshTokenRepository, $events);

        $token = $repository->getNewRefreshToken();

        $this->assertInstanceOf(RefreshToken::class, $token);
    }
}
