<?php

use Laravel\Passport\Passport;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\Bridge\Scope;
use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\ScopeRepository;

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

    public function test_invalid_scopes_are_removed_when_passport_uses_client_scopes()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        Passport::useClientScopes();

        $repository = new ScopeRepository;

        $scopes = $repository->finalizeScopes(
            [$scope1 = new Scope('scope-1'), new Scope('scope-2')], 'client_credentials', new Client('id', 'name', 'http://localhost'), 1
        );

        $this->assertEquals([], $scopes);
    }

    public function test_client_scopes_are_only_ones_applied_when_passport_uses_client_scopes()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
            'scope-2' => 'description'
        ]);

        Passport::useClientScopes();

        $repository = new ScopeRepository;

        $scope1 = new Scope('scope-1');

        $client = new Client('id', 'name', 'http://localhost');
        $client->addScope($scope1);

        $scopes = $repository->finalizeScopes(
            [$scope1, new Scope('scope-2')],
            'client_credentials',
            $client,
            1
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
