<?php

namespace Laravel\Passport\Tests;

use Laravel\Passport\Scope;
use PHPUnit\Framework\TestCase;

class ScopeTest extends TestCase
{
    public function test_scope_can_be_converted_to_array()
    {
        $scope = new Scope('user', 'get user information');
        $this->assertEquals([
            'id' => 'user',
            'description' => 'get user information',
        ], $scope->toArray());
    }

    public function test_scope_can_be_converted_to_json()
    {
        $scope = new Scope('user', 'get user information');
        $this->assertEquals(json_encode([
            'id' => 'user',
            'description' => 'get user information',
        ]), $scope->toJson());
    }
}
