<?php

namespace Laravel\Passport\Tests\Unit;

use Laravel\Passport\AccessToken;
use Laravel\Passport\Passport;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class AccessTokenTest extends TestCase
{
    protected function tearDown(): void
    {
        Passport::$withInheritedScopes = false;
    }

    public function test_token_attributes_are_accessible()
    {
        $token = new AccessToken([
            'oauth_user_id' => 1,
            'oauth_client_id' => 2,
            'oauth_access_token_id' => 'token',
            'oauth_scopes' => ['*'],
            'foo' => 'bar',
        ]);

        $this->assertFalse($token->transient());

        $this->assertSame(1, $token->oauth_user_id);
        $this->assertSame(2, $token->oauth_client_id);
        $this->assertSame('token', $token->oauth_access_token_id);
        $this->assertSame(['*'], $token->oauth_scopes);
        $this->assertSame('bar', $token->foo);

        $this->assertTrue(isset($token->oauth_user_id));
        $this->assertTrue(isset($token->oauth_client_id));
        $this->assertTrue(isset($token->oauth_access_token_id));
        $this->assertTrue(isset($token->oauth_scopes));
        $this->assertTrue(isset($token->foo));
    }

    public function test_token_can_determine_if_it_has_scopes()
    {
        $token = new AccessToken(['oauth_scopes' => ['user']]);

        $this->assertTrue($token->can('user'));
        $this->assertFalse($token->can('something'));
        $this->assertTrue($token->cant('something'));
        $this->assertFalse($token->cant('user'));

        $this->assertTrue($token->cant('user:read'));

        $token = new AccessToken(['oauth_scopes' => ['*']]);
        $this->assertTrue($token->can('user'));
        $this->assertTrue($token->can('something'));
    }

    public function test_token_can_determine_if_it_has_inherited_scopes()
    {
        Passport::$withInheritedScopes = true;

        $token = new AccessToken([
            'oauth_scopes' => [
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

        $token = new AccessToken(['oauth_scopes' => ['*']]);
        $this->assertTrue($token->can('user'));
        $this->assertTrue($token->can('something'));
        $this->assertTrue($token->can('admin:webhooks:write'));
    }

    public function test_token_resolves_inherited_scopes()
    {
        $token = new AccessToken;

        $reflector = new ReflectionObject($token);
        $method = $reflector->getMethod('resolveInheritedScopes');
        $method->setAccessible(true);
        $inheritedScopes = $method->invoke($token, 'admin:webhooks:read');

        $this->assertSame([
            'admin',
            'admin:webhooks',
            'admin:webhooks:read',
        ], $inheritedScopes);
    }
}
