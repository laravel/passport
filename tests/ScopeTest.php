<?php

class ScopeTest extends PHPUnit_Framework_TestCase
{
    public function test_scope_can_be_converted_to_array()
    {
        $scope = new Laravel\Passport\Scope('user', 'get user information');
        $this->assertEquals([
            'id' => 'user',
            'description' => 'get user information',
        ], $scope->toArray());
    }

    public function test_scope_can_be_converted_to_json()
    {
        $scope = new Laravel\Passport\Scope('user', 'get user information');
        $this->assertEquals(json_encode([
            'id' => 'user',
            'description' => 'get user information',
        ]), $scope->toJson());
    }
}
