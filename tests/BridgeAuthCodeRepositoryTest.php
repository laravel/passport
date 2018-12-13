<?php

namespace Laravel\Passport\Tests;

use Mockery as m;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\Bridge\Scope;
use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\AuthCode;
use Laravel\Passport\Bridge\AuthCodeRepository;

class BridgeAuthCodeRepositoryTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_auth_codes_can_be_persisted()
    {
        $expiration = Carbon::now();

        $codeRepository = m::mock('Laravel\Passport\AuthCodeRepository');

        $codeRepository->shouldReceive('create')->once()->andReturnUsing(function ($array) use ($expiration) {
            $this->assertEquals(1, $array['id']);
            $this->assertEquals(2, $array['user_id']);
            $this->assertEquals('client-id', $array['client_id']);
            $this->assertEquals(['scopes'], json_decode($array['scopes'], true));
            $this->assertEquals(false, $array['revoked']);
            $this->assertEquals($expiration, $array['expires_at']);
        });

        $authCode = new AuthCode();
        $authCode->setIdentifier(1);
        $authCode->addScope(new Scope('scopes'));
        $authCode->setExpiryDateTime($expiration);
        $authCode->setUserIdentifier(2);
        $authCode->setClient(new Client('client-id', 'name', 'redirect'));

        $repository = new AuthCodeRepository($codeRepository);

        $repository->persistNewAuthCode($authCode);
    }
}
