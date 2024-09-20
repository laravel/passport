<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Container\Container;
use Laravel\Passport\AccessToken;
use Laravel\Passport\HasApiTokens;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class HasApiTokensTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function tearDown(): void
    {
        Container::getInstance()->flush();
    }

    public function test_token_can_indicates_if_token_has_given_scope()
    {
        $user = new HasApiTokensTestStub;
        $token = m::mock(AccessToken::class);
        $token->shouldReceive('can')->with('scope')->andReturn(true);
        $token->shouldReceive('can')->with('another-scope')->andReturn(false);

        $this->assertTrue($user->withAccessToken($token)->tokenCan('scope'));
        $this->assertFalse($user->withAccessToken($token)->tokenCan('another-scope'));
    }
}

class HasApiTokensTestStub
{
    use HasApiTokens;

    public function getAuthIdentifier()
    {
        return 1;
    }
}
