<?php

namespace Laravel\Passport\Tests;

use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Passport::$withInheritedScopes = false;
    }

    public function test_token_can_determine_if_it_has_scopes()
    {
        $token = new Token(['scopes' => ['user']]);

        $this->assertTrue($token->can('user'));
        $this->assertFalse($token->can('something'));
        $this->assertTrue($token->cant('something'));
        $this->assertFalse($token->cant('user'));

        $this->assertTrue($token->cant('user:read'));

        $token = new Token(['scopes' => ['*']]);
        $this->assertTrue($token->can('user'));
        $this->assertTrue($token->can('something'));
    }

    public function test_token_can_determine_if_it_has_inherited_scopes()
    {
        Passport::$withInheritedScopes = true;

        $token = new Token([
            'scopes' => [
                'user',
                'group',
                'admin:webhooks:read',
            ],
        ]);

        $this->assertTrue($token->can('user'));
        $this->assertTrue($token->can('group'));
        $this->assertTrue($token->can('user:read'));
        $this->assertTrue($token->can('group:read'));
        $this->assertTrue($token->can('admin:webhooks:read'));

        $this->assertTrue($token->cant('admin:webhooks'));

        $this->assertFalse($token->can('something'));

        $token = new Token(['scopes' => ['*']]);
        $this->assertTrue($token->can('user'));
        $this->assertTrue($token->can('something'));
        $this->assertTrue($token->can('admin:webhooks:write'));
    }
}
