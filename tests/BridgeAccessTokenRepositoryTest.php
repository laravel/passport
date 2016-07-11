<?php

use Carbon\Carbon;

class BridgeAccessTokenRepositoryTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function test_access_tokens_can_be_persisted()
    {
        $expiration = Carbon::now();

        $database = Mockery::mock('Illuminate\Database\Connection');

        $database->shouldReceive('table->insert')->once()->with([
            'id' => 1,
            'user_id' => 2,
            'client_id' => 'client-id',
            'scopes' => json_encode(['scopes']),
            'revoked' => false,
            'expires_at' => $expiration,
        ]);

        $accessToken = new Laravel\Passport\Bridge\AccessToken(2, [new Laravel\Passport\Bridge\Scope('scopes')]);
        $accessToken->setIdentifier(1);
        $accessToken->setExpiryDateTime($expiration);
        $accessToken->setClient(new Laravel\Passport\Bridge\Client('client-id', 'name', 'redirect'));

        $repository = new Laravel\Passport\Bridge\AccessTokenRepository($database);

        $repository->persistNewAccessToken($accessToken);
    }
}
