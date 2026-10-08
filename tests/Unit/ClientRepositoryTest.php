<?php

namespace Laravel\Passport\Tests\Unit;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\QueryException;
use Illuminate\Support\Once;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use PDOException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ClientRepositoryTest extends TestCase
{
    protected bool $clientUuids;
    protected string $clientModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientUuids = Passport::$clientUuids;
        $this->clientModel = Passport::clientModel();
        Passport::$clientUuids = true;
        Passport::useClientModel(ClientRepositoryTestClient::class);
        Once::flush();
    }

    protected function tearDown(): void
    {
        Passport::$clientUuids = $this->clientUuids;
        Passport::useClientModel($this->clientModel);
        ClientRepositoryTestClient::$query = null;
        Once::flush();

        parent::tearDown();
    }

    public function test_malformed_uuid_client_ids_do_not_execute_a_lookup()
    {
        ClientRepositoryTestClient::$query = fn () => throw new RuntimeException('Unexpected client lookup.');

        $repository = new ClientRepository;

        foreach (['x', '', '1', 1, '550e8400-e29b-41d4-a716-44665544000x'] as $id) {
            $this->assertNull($repository->find($id));
        }
    }

    public function test_valid_uuid_client_ids_are_looked_up()
    {
        $id = '550e8400-e29b-41d4-a716-446655440000';
        $client = new Client;
        $query = $this->createMock(Builder::class);
        $query->expects($this->once())->method('find')->with($id)->willReturn($client);
        ClientRepositoryTestClient::$query = fn () => $query;

        $this->assertSame($client, (new ClientRepository)->find($id));
    }

    public function test_non_uuid_client_ids_are_looked_up_when_uuid_clients_are_disabled()
    {
        Passport::$clientUuids = false;

        foreach ([1, '1', 'x'] as $id) {
            Once::flush();
            $client = new Client;
            $query = $this->createMock(Builder::class);
            $query->expects($this->once())->method('find')->with($id)->willReturn($client);
            ClientRepositoryTestClient::$query = fn () => $query;

            $this->assertSame($client, (new ClientRepository)->find($id));
        }
    }

    public function test_custom_unique_id_client_ids_are_validated_by_the_model()
    {
        Passport::useClientModel(ClientRepositoryTestUlidClient::class);

        $id = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
        $client = new Client;
        $query = $this->createMock(Builder::class);
        $query->expects($this->once())->method('find')->with($id)->willReturn($client);
        ClientRepositoryTestClient::$query = fn () => $query;

        $repository = new ClientRepository;

        $this->assertNull($repository->find('x'));
        $this->assertNull($repository->find(1));
        $this->assertNull($repository->find('550e8400-e29b-41d4-a716-446655440000'));
        $this->assertSame($client, $repository->find($id));
    }

    public function test_lookup_failures_are_not_caught()
    {
        $exception = new QueryException('testing', 'select * from oauth_clients', [], new PDOException('Database unavailable.'));
        ClientRepositoryTestClient::$query = fn () => throw $exception;
        $this->expectExceptionObject($exception);

        (new ClientRepository)->find('550e8400-e29b-41d4-a716-446655440000');
    }
}

class ClientRepositoryTestClient extends Client
{
    public static ?Closure $query = null;

    public function newQuery()
    {
        return (static::$query)();
    }
}

class ClientRepositoryTestUlidClient extends ClientRepositoryTestClient
{
    use HasUlids;
}
