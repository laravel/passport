<?php

namespace Laravel\Passport\Tests;

use Carbon\Carbon;
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
        $expiration = Carbon::now();

        $tokenRepository = m::mock(TokenRepository::class);
        $events = m::mock(Dispatcher::class);

        $tokenRepository->shouldReceive('create')->once()->andReturnUsing(function ($array) use ($expiration) {
            $this->assertEquals(1, $array['id']);
            $this->assertEquals(2, $array['user_id']);
            $this->assertEquals('client-id', $array['client_id']);
            $this->assertEquals(['scopes'], $array['scopes']);
            $this->assertEquals(false, $array['revoked']);
            $this->assertInstanceOf('DateTime', $array['created_at']);
            $this->assertInstanceOf('DateTime', $array['updated_at']);
            $this->assertEquals($expiration, $array['expires_at']);
        });

        $events->shouldReceive('dispatch')->once();

        $accessToken = new AccessToken(2, [new Scope('scopes')]);
        $accessToken->setIdentifier(1);
        $accessToken->setExpiryDateTime($expiration);
        $accessToken->setClient(new Client('client-id', 'name', 'redirect'));

        $repository = new AccessTokenRepository($tokenRepository, $events);

        $repository->persistNewAccessToken($accessToken);
    }
}
