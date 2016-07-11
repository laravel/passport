<?php

class TransientTokenTest extends PHPUnit_Framework_TestCase
{
    public function test_transient_token_can_do_anything()
    {
        $token = new Laravel\Passport\TransientToken;
        $this->assertTrue($token->can('foo'));
        $this->assertFalse($token->cant('foo'));
    }
}
