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

        $events = Mockery::mock('Illuminate\Contracts\Events\Dispatcher');

        $database->shouldReceive('table->insert')->once()->andReturnUsing(function ($array) use ($expiration) {
            $this->assertEquals(1, $array['id']);
            $this->assertEquals(2, $array['user_id']);
            $this->assertEquals('client-id', $array['client_id']);
            $this->assertEquals(json_encode(['scopes']), $array['scopes']);
            $this->assertEquals(false, $array['revoked']);
            $this->assertInstanceOf('DateTime', $array['created_at']);
            $this->assertInstanceOf('DateTime', $array['updated_at']);
            $this->assertEquals($expiration, $array['expires_at']);
        });

        $events->shouldReceive('fire')->once();

        $accessToken = new Laravel\Passport\Bridge\AccessToken(2, [new Laravel\Passport\Bridge\Scope('scopes')]);
        $accessToken->setIdentifier(1);
        $accessToken->setExpiryDateTime($expiration);
        $accessToken->setClient(new Laravel\Passport\Bridge\Client('client-id', 'name', 'redirect'));

        $repository = new Laravel\Passport\Bridge\AccessTokenRepository($database, $events);

        $repository->persistNewAccessToken($accessToken);
    }
}
