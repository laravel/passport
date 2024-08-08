<?php

namespace Laravel\Passport\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Passport\Bridge\AccessToken;
use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\RefreshToken;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Mockery as m;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;

class BridgeRefreshTokenRepositoryTest extends PassportTestCase
{
    use WithLaravelMigrations;

    public function test_access_tokens_can_be_persisted()
    {
        $expiration = CarbonImmutable::now();

        $events = m::mock(Dispatcher::class);

        $events->shouldReceive('dispatch')->once();

        $accessToken = new AccessToken('3', [], m::mock(Client::class));
        $accessToken->setIdentifier('2');

        $refreshToken = new RefreshToken;
        $refreshToken->setIdentifier('1');
        $refreshToken->setExpiryDateTime($expiration);
        $refreshToken->setAccessToken($accessToken);

        $repository = new RefreshTokenRepository($events);

        $repository->persistNewRefreshToken($refreshToken);

        $this->assertDatabaseHas('oauth_refresh_tokens', [
            'id' => '1',
            'access_token_id' => '2',
            'revoked' => false,
            'expires_at' => $expiration,
        ]);
    }

    public function test_can_get_new_refresh_token()
    {
        $events = m::mock(Dispatcher::class);
        $repository = new RefreshTokenRepository($events);

        $token = $repository->getNewRefreshToken();

        $this->assertInstanceOf(RefreshToken::class, $token);
    }
}
