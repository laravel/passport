<?php

namespace Laravel\Passport\Tests;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Http\Controllers\ClientController;
use Laravel\Passport\Http\Resources\ClientResource;
use Laravel\Passport\Http\Rules\RedirectRule;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ClientControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $configMock = m::mock(ConfigRepository::class)
            ->shouldReceive('get')
            ->with('passport.json_resource_wrapper', null)
            ->andReturn('data')->getMock();

        app()->instance('config', $configMock);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function test_that_the_json_resource_wraps_in_data()
    {
        $this->assertEquals('data', ClientResource::$wrap);
    }

    public function test_all_the_clients_for_the_current_user_can_be_retrieved()
    {
        $client1 = new Client;
        $client2 = new Client;

        $client = m::mock(collect([$client1, $client2]));
        $client->shouldReceive('makeVisible')->andReturn($client);

        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('activeForUser')->with(1)->andReturn($client);

        $request = m::mock(Request::class);
        $request->shouldReceive('user')->andReturn(new ClientControllerFakeUser);

        $controller = new ClientController(
            $clients,
            m::mock(Factory::class),
            m::mock(RedirectRule::class)
        );

        $this->assertInstanceOf(JsonResource::class, $controller->forUser($request));
        $this->assertCount(2, $controller->forUser($request)->collection);
        $this->assertInstanceOf(ClientResource::class, $controller->forUser($request)->collection[0]);
        $this->assertEquals($client1, $controller->forUser($request)->collection[0]->resource);
    }

    public function test_clients_can_be_stored()
    {
        $clients = m::mock(ClientRepository::class);

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);
        $request->setUserResolver(function () {
            return new ClientControllerFakeUser;
        });

        $clients->shouldReceive('create')
            ->once()
            ->with(1, 'client name', 'http://localhost', false, false, true)
            ->andReturn($client = new Client);

        $redirectRule = m::mock(RedirectRule::class);

        $validator = m::mock(Factory::class);
        $validator->shouldReceive('make')->once()->with([
            'name' => 'client name',
            'redirect' => 'http://localhost',
        ], [
            'name' => 'required|max:255',
            'redirect' => ['required', $redirectRule],
            'confidential' => 'boolean',
        ])->andReturn($validator);
        $validator->shouldReceive('validate')->once();

        $controller = new ClientController(
            $clients, $validator, $redirectRule
        );

        $resource = $controller->store($request);
        $this->assertInstanceOf(ClientResource::class, $resource);
        $this->assertEquals($client, $resource->resource);
    }

    public function test_public_clients_can_be_stored()
    {
        $clients = m::mock(ClientRepository::class);

        $request = Request::create(
            '/',
            'GET',
            ['name' => 'client name', 'redirect' => 'http://localhost', 'confidential' => false]
        );
        $request->setUserResolver(function () {
            return new ClientControllerFakeUser;
        });

        $clients->shouldReceive('create')
            ->once()
            ->with(1, 'client name', 'http://localhost', false, false, false)
            ->andReturn($client = new Client);

        $redirectRule = m::mock(RedirectRule::class);

        $validator = m::mock(Factory::class);
        $validator->shouldReceive('make')->once()->with([
            'name' => 'client name',
            'redirect' => 'http://localhost',
            'confidential' => false,
        ], [
            'name' => 'required|max:255',
            'redirect' => ['required', $redirectRule],
            'confidential' => 'boolean',
        ])->andReturn($validator);
        $validator->shouldReceive('validate')->once();

        $controller = new ClientController(
            $clients, $validator, $redirectRule
        );

        $resource = $controller->store($request);
        $this->assertInstanceOf(ClientResource::class, $resource);
        $this->assertEquals($client, $resource->resource);
    }

    public function test_clients_can_be_updated()
    {
        $clients = m::mock(ClientRepository::class);
        $client = m::mock(Client::class);
        $clients->shouldReceive('findForUser')->with(1, 1)->andReturn($client);

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);

        $request->setUserResolver(function () {
            $user = m::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $clients->shouldReceive('update')->once()->with(
            m::type(Client::class), 'client name', 'http://localhost'
        )->andReturn('response');

        $redirectRule = m::mock(RedirectRule::class);

        $validator = m::mock(Factory::class);
        $validator->shouldReceive('make')->once()->with([
            'name' => 'client name',
            'redirect' => 'http://localhost',
        ], [
            'name' => 'required|max:255',
            'redirect' => ['required', $redirectRule],
        ])->andReturn($validator);
        $validator->shouldReceive('validate')->once();

        $controller = new ClientController(
            $clients, $validator, $redirectRule
        );

        $resource = $controller->update($request, 1);
        $this->assertInstanceOf(ClientResource::class, $resource);
        $this->assertEquals('response', $resource->resource);
    }

    public function test_404_response_if_client_doesnt_belong_to_user()
    {
        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('findForUser')->with(1, 1)->andReturnNull();

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);

        $request->setUserResolver(function () {
            $user = m::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $clients->shouldReceive('update')->never();

        $validator = m::mock(Factory::class);

        $controller = new ClientController(
            $clients, $validator, m::mock(RedirectRule::class)
        );

        $this->assertEquals(404, $controller->update($request, 1)->status());
    }

    public function test_clients_can_be_deleted()
    {
        $clients = m::mock(ClientRepository::class);
        $client = m::mock(Client::class);
        $clients->shouldReceive('findForUser')->with(1, 1)->andReturn($client);

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);

        $request->setUserResolver(function () {
            $user = m::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $clients->shouldReceive('delete')->once()->with(
            m::type(Client::class)
        )->andReturn('response');

        $validator = m::mock(Factory::class);

        $controller = new ClientController(
            $clients, $validator, m::mock(RedirectRule::class)
        );

        $response = $controller->destroy($request, 1);

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->status());
    }

    public function test_404_response_if_client_doesnt_belong_to_user_on_delete()
    {
        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('findForUser')->with(1, 1)->andReturnNull();

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);

        $request->setUserResolver(function () {
            $user = m::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $clients->shouldReceive('delete')->never();

        $validator = m::mock(Factory::class);

        $controller = new ClientController(
            $clients, $validator, m::mock(RedirectRule::class)
        );

        $this->assertEquals(404, $controller->destroy($request, 1)->status());
    }
}

class ClientControllerFakeUser
{
    public $id = 1;

    public function getKey()
    {
        return $this->id;
    }
}
