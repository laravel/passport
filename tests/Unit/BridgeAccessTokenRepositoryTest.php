<?php

namespace Laravel\Passport\Tests\Unit;

use Carbon\CarbonImmutable;
use DateTime;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Passport\Bridge\AccessToken;
use Laravel\Passport\Bridge\AccessTokenRepository;
use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\Scope;
use Laravel\Passport\TokenRepository;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class BridgeAccessTokenRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_access_tokens_can_be_persisted()
    {
        $expiration = CarbonImmutable::now();

        $tokenRepository = m::mock(TokenRepository::class);
        $events = m::mock(Dispatcher::class);

        $tokenRepository->shouldReceive('create')->once()->andReturnUsing(function ($array) use ($expiration) {
            $this->assertSame(1, $array['id']);
            $this->assertSame(2, $array['user_id']);
            $this->assertSame('client-id', $array['client_id']);
            $this->assertEquals(['scopes'], $array['scopes']);
            $this->assertEquals(false, $array['revoked']);
            $this->assertInstanceOf(DateTime::class, $array['created_at']);
            $this->assertInstanceOf(DateTime::class, $array['updated_at']);
            $this->assertEquals($expiration, $array['expires_at']);
        });

        $events->shouldReceive('dispatch')->once();

        $accessToken = new AccessToken(2, [new Scope('scopes')], new Client('client-id', 'name', 'redirect'));
        $accessToken->setIdentifier(1);
        $accessToken->setExpiryDateTime($expiration);

        $repository = new AccessTokenRepository($tokenRepository, $events);

        $repository->persistNewAccessToken($accessToken);
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
        $tokenRepository = m::mock(TokenRepository::class);
        $events = m::mock(Dispatcher::class);
        $repository = new AccessTokenRepository($tokenRepository, $events);
        $client = new Client('client-id', 'name', 'redirect');
        $scopes = [new Scope('place-orders'), new Scope('check-status')];
        $userIdentifier = 123;

        $token = $repository->getNewToken($client, $scopes, $userIdentifier);

        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertEquals($client, $token->getClient());
        $this->assertEquals($scopes, $token->getScopes());
        $this->assertSame($userIdentifier, $token->getUserIdentifier());
    }
}
