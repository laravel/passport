<?php

namespace Laravel\Passport\Tests;

use Mockery as m;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\Bridge\Scope;
use Laravel\Passport\Bridge\Client;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\Bridge\AccessToken;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Passport\Bridge\AccessTokenRepository;

class BridgeAccessTokenRepositoryTest extends TestCase
{
    public function tearDown()
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
