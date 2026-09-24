<?php

namespace Laravel\Passport\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Bridge\AccessToken;
use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\RefreshToken;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Events\RefreshTokenCreated;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;

class BridgeRefreshTokenRepositoryTest extends PassportTestCase
{
    use WithLaravelMigrations;

    public function test_access_tokens_can_be_persisted()
    {
        $expiration = CarbonImmutable::now();

        Event::fake();

        $accessToken = new AccessToken('3', [], new Client('client-id', 'name', ['redirect']));
        $accessToken->setIdentifier('2');

        $refreshToken = new RefreshToken;
        $refreshToken->setIdentifier('1');
        $refreshToken->setExpiryDateTime($expiration);
        $refreshToken->setAccessToken($accessToken);

        $repository = new RefreshTokenRepository(app('events'));

        $repository->persistNewRefreshToken($refreshToken);

        $this->assertDatabaseHas('oauth_refresh_tokens', [
            'id' => '1',
            'access_token_id' => '2',
            'revoked' => false,
            'expires_at' => $expiration,
        ]);

        Event::assertDispatched(fn (RefreshTokenCreated $event) => $event->refreshTokenId === '1'
            && $event->accessTokenId === '2');
    }

    public function test_can_get_new_refresh_token()
    {
        $repository = new RefreshTokenRepository(app('events'));

        $token = $repository->getNewRefreshToken();

        $this->assertInstanceOf(RefreshToken::class, $token);
    }
}
