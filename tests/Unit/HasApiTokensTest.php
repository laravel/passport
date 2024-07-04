<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Container\Container;
use Laravel\Passport\AccessToken;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\PersonalAccessTokenFactory;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class HasApiTokensTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
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
