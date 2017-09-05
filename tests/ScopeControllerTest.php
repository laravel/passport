<?php

class ScopeControllerTest extends PHPUnit_Framework_TestCase
{
    public function testShouldGetScopes()
    {
        $controller = new \Laravel\Passport\Http\Controllers\ScopeController;

        \Laravel\Passport\Passport::tokensCan($scopes = [
            'place-orders' => 'Place orders',
            'check-status' => 'Check order status',
        ]);

        $result = $controller->all();

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(\Laravel\Passport\Scope::class, $result);
        $this->assertSame(['id' => 'place-orders', 'description' => 'Place orders'], $result[0]->toArray());
        $this->assertSame(['id' => 'check-status', 'description' => 'Check order status'], $result[1]->toArray());
    }
}
