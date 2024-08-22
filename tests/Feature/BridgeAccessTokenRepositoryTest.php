<?php

namespace Laravel\Passport\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Passport\Bridge\AccessToken;
use Laravel\Passport\Bridge\AccessTokenRepository;
use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\Scope;
use Mockery as m;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;

class BridgeAccessTokenRepositoryTest extends PassportTestCase
{
    use WithLaravelMigrations;

    public function test_access_tokens_can_be_persisted()
    {
        $expiration = CarbonImmutable::now();

        $events = m::mock(Dispatcher::class);

        $events->shouldReceive('dispatch')->once();

        $accessToken = new AccessToken(2, [new Scope('scopes')], new Client('client-id', 'name', ['redirect']));
        $accessToken->setIdentifier(1);
        $accessToken->setExpiryDateTime($expiration);

        $repository = new AccessTokenRepository($events);

        $repository->persistNewAccessToken($accessToken);

        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => '1',
            'user_id' => '2',
            'client_id' => 'client-id',
            'scopes' => '["scopes"]',
            'revoked' => false,
            'expires_at' => $expiration,
        ]);
    }

    public function test_access_tokens_can_be_revoked()
    {
        $tokenRepository = m::mock(TokenRepository::class);
        $events = m::mock(Dispatcher::class);

        $tokenRepository->shouldReceive('revokeAccessToken')->with('token-id')->once()->andReturn(1);
        $events->shouldReceive('dispatch')->once();

        $repository = new AccessTokenRepository($tokenRepository, $events);
        $repository->revokeAccessToken('token-id');

        $this->expectNotToPerformAssertions();
    }

    public function test_access_token_revoke_event_is_not_dispatched_when_nothing_happened()
    {
        $tokenRepository = m::mock(TokenRepository::class);
        $events = m::mock(Dispatcher::class);

        $tokenRepository->shouldReceive('revokeAccessToken')->with('token-id')->once()->andReturn(0);
        $events->shouldNotReceive('dispatch');

        $repository = new AccessTokenRepository($tokenRepository, $events);
        $repository->revokeAccessToken('token-id');

        $this->expectNotToPerformAssertions();
    }

    public function test_can_get_new_access_token()
    {
        $events = m::mock(Dispatcher::class);
        $repository = new AccessTokenRepository($events);
        $client = new Client('client-id', 'name', ['redirect']);
        $scopes = [new Scope('place-orders'), new Scope('check-status')];
        $userIdentifier = 123;

        $token = $repository->getNewToken($client, $scopes, $userIdentifier);

        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertEquals($client, $token->getClient());
        $this->assertEquals($scopes, $token->getScopes());
        $this->assertEquals($userIdentifier, $token->getUserIdentifier());
    }
}
