<?php

namespace Laravel\Passport\Tests;

use Mockery as m;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\ClientRepository;
use Illuminate\Contracts\Validation\Factory;
use Laravel\Passport\Http\Rules\RedirectRule;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Passport\Http\Controllers\ClientController;

class ClientControllerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_all_the_clients_for_the_current_user_can_be_retrieved()
    {
        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('activeForUser')->once()->with(1)->andReturn($client = m::mock());
        $client->shouldReceive('makeVisible')->with('secret')->andReturn($client);

        $request = m::mock(Request::class);
        $request->shouldReceive('user')->andReturn(new ClientControllerFakeUser);

        $controller = new ClientController(
            $clients,
            m::mock(Factory::class),
            m::mock(RedirectRule::class)
        );

        $this->assertEquals($client, $controller->forUser($request));
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
            ->with(1, 'client name', 'http://localhost')
            ->andReturn($client = new Client);

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

        $this->assertEquals($client, $controller->store($request));
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

        $this->assertEquals('response', $controller->update($request, 1));
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
