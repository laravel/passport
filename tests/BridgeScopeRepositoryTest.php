<?php

use Laravel\Passport\Passport;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\Bridge\Scope;
use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\ScopeRepository;
use Laravel\Passport\Client as ClientModel;
use Laravel\Passport\ClientRepository;

class BridgeScopeRepositoryTest extends TestCase
{
    public function test_invalid_scopes_are_removed()
    {
        Passport::tokensCan([
            'scope-1' => 'description',
        ]);

        $client = Mockery::mock(ClientModel::class);
        $client->shouldReceive('hasScope')->andReturn(true);

        $clients = Mockery::mock(ClientRepository::class);
        $clients->shouldReceive('findActive')->withAnyArgs()->andReturn($client);

        $repository = new ScopeRepository($clients);

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

        $clients = Mockery::mock(ClientRepository::class);
        $clients->shouldReceive('findActive')->withAnyArgs();

        $repository = new ScopeRepository($clients);

        $scopes = $repository->finalizeScopes(
            [$scope1 = new Scope('*')], 'client_credentials', new Client('id', 'name', 'http://localhost'), 1
        );

        $this->assertEquals([], $scopes);
    }

    public function test_scopes_which_client_cant_issue_are_removed()
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
            [$scope1 = new Scope('scope-1')], 'client_credentials', new Client('id', 'name', 'http://localhost'), 1
        );

        $this->assertEquals([], $scopes);
    }
}
