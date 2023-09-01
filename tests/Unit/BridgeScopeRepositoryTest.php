<?php

namespace Laravel\Passport\Tests\Unit;

use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\Scope;
use Laravel\Passport\Bridge\ScopeRepository;
use Laravel\Passport\Client as ClientModel;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Mockery;
use PHPUnit\Framework\TestCase;

class BridgeScopeRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Passport::$withInheritedScopes = false;
    }

    public function test_invalid_scopes_are_removed()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $client = Mockery::mock(ClientModel::class)->makePartial();

        $clients = Mockery::mock(ClientRepository::class);
        $clients->shouldReceive('findActive')->withAnyArgs()->andReturn($client);

        $repository = new ScopeRepository($clients);

        $scopes = $repository->finalizeScopes(
            [$scope1 = new Scope('scope-1'), new Scope('scope-2')], 'client_credentials', new Client('id', 'name', 'http://localhost'), 1
        );

        $this->assertEquals([$scope1], $scopes);
    }

    public function test_invalid_scopes_are_removed_without_a_client_repository()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $repository = new ScopeRepository();

        $scopes = $repository->finalizeScopes(
            [$scope1 = new Scope('scope-1'), new Scope('scope-2')], 'client_credentials', new Client('id', 'name', 'http://localhost'), 1
        );

        $this->assertEquals([$scope1], $scopes);
    }

    public function test_clients_do_not_restrict_scopes_by_default()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
            'scope-2' => 'description',
        ]);

        $client = Mockery::mock(ClientModel::class)->makePartial();
        $client->scopes = null;

        $clients = Mockery::mock(ClientRepository::class);
        $clients->shouldReceive('findActive')->withAnyArgs()->andReturn($client);

        $repository = new ScopeRepository($clients);

        $scopes = $repository->finalizeScopes(
            [$scope1 = new Scope('scope-1'), $scope2 = new Scope('scope-2')], 'client_credentials', new Client('id', 'name', 'http://localhost'), 1
        );

        $this->assertEquals([$scope1, $scope2], $scopes);
    }

    public function test_scopes_disallowed_for_client_are_removed()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
            'scope-2' => 'description',
        ]);

        $client = Mockery::mock(ClientModel::class)->makePartial();
        $client->scopes = ['scope-1'];

        $clients = Mockery::mock(ClientRepository::class);
        $clients->shouldReceive('findActive')->withAnyArgs()->andReturn($client);

        $repository = new ScopeRepository($clients);

        $scopes = $repository->finalizeScopes(
            [$scope1 = new Scope('scope-1'), new Scope('scope-2')], 'client_credentials', new Client('id', 'name', 'http://localhost'), 1
        );

        $this->assertEquals([$scope1], $scopes);
    }

    public function test_scopes_disallowed_for_client_are_removed_but_inherited_scopes_are_not()
    {
        Passport::$withInheritedScopes = true;

        Passport::tokensCan([
            'scope-1' => 'description',
            'scope-1:limited-access' => 'description',
            'scope-2' => 'description',
        ]);

        $client = Mockery::mock(ClientModel::class)->makePartial();
        $client->scopes = ['scope-1'];

        $clients = Mockery::mock(ClientRepository::class);
        $clients->shouldReceive('findActive')->withAnyArgs()->andReturn($client);

        $repository = new ScopeRepository($clients);

        $scopes = $repository->finalizeScopes(
            [$scope1 = new Scope('scope-1:limited-access'), new Scope('scope-2')], 'client_credentials', new Client('id', 'name', 'http://localhost'), 1
        );

        $this->assertEquals([$scope1], $scopes);
    }

    public function test_superuser_scope_cant_be_applied_if_wrong_grant()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $client = Mockery::mock(ClientModel::class)->makePartial();

        $clients = Mockery::mock(ClientRepository::class);
        $clients->shouldReceive('findActive')->withAnyArgs()->andReturn($client);

        $repository = new ScopeRepository($clients);

        $scopes = $repository->finalizeScopes(
            [$scope1 = new Scope('*')], 'refresh_token', new Client('id', 'name', 'http://localhost'), 1
        );

        $this->assertEquals([], $scopes);
    }

    public function test_superuser_scope_cant_be_applied_if_wrong_grant_without_a_client_repository()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $repository = new ScopeRepository();

        $scopes = $repository->finalizeScopes(
            [$scope1 = new Scope('*')], 'refresh_token', new Client('id', 'name', 'http://localhost'), 1
        );

        $this->assertEquals([], $scopes);
    }
}
