<?php

namespace Laravel\Passport\Tests;

use PHPUnit\Framework\TestCase;
use Laravel\Passport\TransientToken;

class TransientTokenTest extends TestCase
{
    public function test_transient_token_can_do_anything()
    {
        $token = new TransientToken;
        $this->assertTrue($token->can('foo'));
        $this->assertFalse($token->cant('foo'));
    }
}
