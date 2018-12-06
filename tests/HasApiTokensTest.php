<?php

namespace Laravel\Passport\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\HasApiTokens;
use Illuminate\Container\Container;
use Laravel\Passport\PersonalAccessTokenFactory;

class HasApiTokensTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_token_can_indicates_if_token_has_given_scope()
    {
        $user = new HasApiTokensTestStub;
        $token = m::mock();
        $token->shouldReceive('can')->with('scope')->andReturn(true);
        $token->shouldReceive('can')->with('another-scope')->andReturn(false);

        $this->assertTrue($user->withAccessToken($token)->tokenCan('scope'));
        $this->assertFalse($user->withAccessToken($token)->tokenCan('another-scope'));
    }

    public function test_token_can_be_created()
    {
        $container = new Container;
        Container::setInstance($container);
        $container->instance(PersonalAccessTokenFactory::class, $factory = m::mock());
        $factory->shouldReceive('make')->once()->with(1, 'name', ['scopes']);
        $user = new HasApiTokensTestStub;

        $user->createToken('name', ['scopes']);
    }
}

class HasApiTokensTestStub
{
    use HasApiTokens;

    public function getKey()
    {
        return 1;
    }
}
