<?php

namespace Laravel\Passport\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Bridge\AccessToken;
use Laravel\Passport\Bridge\AccessTokenRepository;
use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\Scope;
use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Events\AccessTokenRevoked;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;

class BridgeAccessTokenRepositoryTest extends PassportTestCase
{
    use WithLaravelMigrations;

    public function test_access_tokens_can_be_persisted()
    {
        $expiration = CarbonImmutable::now();

        Event::fake();

        $accessToken = new AccessToken(2, [new Scope('scopes')], new Client('client-id', 'name', ['redirect']));
        $accessToken->setIdentifier(1);
        $accessToken->setExpiryDateTime($expiration);

        $repository = new AccessTokenRepository(app('events'));

        $repository->persistNewAccessToken($accessToken);

        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => '1',
            'user_id' => '2',
            'client_id' => 'client-id',
            'scopes' => '["scopes"]',
            'revoked' => false,
            'expires_at' => $expiration,
        ]);

        Event::assertDispatched(fn (AccessTokenCreated $event) => $event->tokenId === '1'
            && $event->userId === '2'
            && $event->clientId === 'client-id');
    }

    public function test_access_tokens_can_be_revoked()
    {
        Event::fake();

        $accessToken = new AccessToken(2, [], new Client('client-id', 'name', ['redirect']));
        $accessToken->setIdentifier('token-id');
        $accessToken->setExpiryDateTime(CarbonImmutable::now());

        $repository = new AccessTokenRepository(app('events'));
        $repository->persistNewAccessToken($accessToken);

        $repository->revokeAccessToken('token-id');

        $this->assertDatabaseHas('oauth_access_tokens', ['id' => 'token-id', 'revoked' => true]);
        Event::assertDispatched(fn (AccessTokenRevoked $event) => $event->tokenId === 'token-id');
    }

    public function test_access_token_revoke_event_is_not_dispatched_when_nothing_happened()
    {
        Event::fake();

        $repository = new AccessTokenRepository(app('events'));
        $repository->revokeAccessToken('token-id');

        Event::assertNotDispatched(AccessTokenRevoked::class);
    }

    public function test_can_get_new_access_token()
    {
        $repository = new AccessTokenRepository(app('events'));
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
