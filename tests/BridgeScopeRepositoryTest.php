<?php

use ROMaster2\Passport\Passport;
use PHPUnit\Framework\TestCase;
use ROMaster2\Passport\Bridge\Scope;
use ROMaster2\Passport\Bridge\Client;
use ROMaster2\Passport\Bridge\ScopeRepository;

class BridgeScopeRepositoryTest extends TestCase
{
    public function test_invalid_scopes_are_removed()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $repository = new ScopeRepository;

        $scopes = $repository->finalizeScopes(
            [$scope1 = new Scope('scope-1'), new Scope('scope-2')], 'client_credentials', new Client('id', 'name', 'http://localhost'), 1
        );

        $this->assertEquals([$scope1], $scopes);
    }

    public function test_superuser_scope_cant_be_applied_if_wrong_grant()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $repository = new ScopeRepository;

        $scopes = $repository->finalizeScopes(
            [$scope1 = new Scope('*')], 'client_credentials', new Client('id', 'name', 'http://localhost'), 1
        );

        $this->assertEquals([], $scopes);
    }
}
