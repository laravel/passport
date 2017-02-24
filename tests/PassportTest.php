<?php

use Laravel\Passport\Passport;

class PassportTest extends PHPUnit_Framework_TestCase
{
    public function test_scopes_can_be_managed()
    {
        Passport::tokensCan([
            'user' => 'get user information',
        ]);

        $this->assertTrue(Passport::hasScope('user'));
        $this->assertEquals(['user'], Passport::scopeIds());
        $this->assertEquals('user', Passport::scopes()[0]->id);
    }

    public function test_scopes_can_have_star()
    {
        $this->assertTrue(Passport::hasScope('*'));
    }

    public function test_scopes_cannot_have_star_when_disallowed()
    {
        Passport::disallowStarScope();

        $this->assertFalse(Passport::hasScope('*'));

        Passport::$allowStarScope = true;
    }
}
