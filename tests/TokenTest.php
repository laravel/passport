<?php

use Laravel\Passport\Passport;

class TokenTest extends PHPUnit_Framework_TestCase
{
    public function test_token_can_determine_if_it_has_scopes()
    {
        $token = new Laravel\Passport\Token(['scopes' => ['user']]);

        $this->assertTrue($token->can('user'));
        $this->assertFalse($token->can('something'));
        $this->assertTrue($token->cant('something'));
        $this->assertFalse($token->cant('user'));

        $token = new Laravel\Passport\Token(['scopes' => ['*']]);
        $this->assertTrue($token->can('user'));
        $this->assertTrue($token->can('something'));
    }

    public function test_token_cannot_use_star_scopes_if_disallowed()
    {
        Passport::disallowStarScope();

        $token = new Laravel\Passport\Token(['scopes' => ['*']]);
        $this->assertFalse($token->can('user'));
        $this->assertFalse($token->can('something'));
        $this->assertTrue($token->cant('something'));

        $token = new Laravel\Passport\Token(['scopes' => ['*', 'user']]);
        $this->assertTrue($token->can('user'));
        $this->assertFalse($token->can('something'));
        $this->assertTrue($token->cant('something'));

        Passport::$allowStarScope = true;
    }
}
