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

    public function test_can_set_max_attempts_for_throttle()
    {
        $this->assertEquals(60, Passport::maxAttempts());

        Passport::maxAttempts(5);

        $this->assertEquals(5, Passport::maxAttempts());
    }

    public function test_can_set_decay_minutes_for_throttle()
    {
        $this->assertEquals(1, Passport::decayMinutes());

        Passport::decayMinutes(5);

        $this->assertEquals(5, Passport::decayMinutes());
    }
}
