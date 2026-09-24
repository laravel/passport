<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Container\Container;
use Illuminate\Foundation\Auth\User as Authenticatable;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use PHPUnit\Framework\TestCase;

class HasApiTokensTest extends TestCase
{
    use VerifiesDoubles;

    protected function tearDown(): void
    {
        Container::getInstance()->flush();
    }

    public function test_token_can_indicates_if_token_has_given_scope()
    {
        $user = new HasApiTokensTestStub;
        $token = Double::for(AccessToken::class);
        $token->expects('can')->with('scope')->returns(true);
        $token->expects('can')->with('another-scope')->returns(false);

        $this->assertTrue($user->withAccessToken($token)->tokenCan('scope'));
        $this->assertFalse($user->withAccessToken($token)->tokenCan('another-scope'));
    }
}

class HasApiTokensTestStub extends Authenticatable implements OAuthenticatable
{
    use HasApiTokens;

    public function getAuthIdentifier()
    {
        return 1;
    }
}
