<?php

namespace Laravel\Passport\Tests;

use Laravel\Passport\Token;
use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase
{
    public function test_token_can_determine_if_it_has_scopes()
    {
        $token = new Token(['scopes' => ['user']]);

        $this->assertTrue($token->can('user'));
        $this->assertFalse($token->can('something'));
        $this->assertTrue($token->cant('something'));
        $this->assertFalse($token->cant('user'));

        $token = new Token(['scopes' => ['*']]);
        $this->assertTrue($token->can('user'));
        $this->assertTrue($token->can('something'));
    }
}
