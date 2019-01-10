<?php

namespace Laravel\Passport\Tests;

use Mockery as m;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\Http\Rules\RedirectRule;
use Laravel\Passport\Http\Controllers\ClientController;

class ClientControllerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_all_the_clients_for_the_current_user_can_be_retrieved()
    {
        $clients = m::mock('Laravel\Passport\ClientRepository');
        $clients->shouldReceive('activeForUser')->once()->with(1)->andReturn($client = m::mock());
        $client->shouldReceive('makeVisible')->with('secret')->andReturn($client);

        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('user')->andReturn(new ClientControllerFakeUser);

        $controller = new ClientController(
            $clients,
            m::mock('Illuminate\Contracts\Validation\Factory'),
            m::mock(RedirectRule::class)
        );

        $this->assertEquals($client, $controller->forUser($request));
    }

    public function test_clients_can_be_stored()
    {
        $clients = m::mock('Laravel\Passport\ClientRepository');

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);
        $request->setUserResolver(function () {
            return new ClientControllerFakeUser;
        });

        $clients->shouldReceive('create')
            ->once()
            ->with(1, 'client name', 'http://localhost')
            ->andReturn($client = new Client);

        $redirectRule = m::mock(RedirectRule::class);

        $validator = m::mock('Illuminate\Contracts\Validation\Factory');
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
        $clients = m::mock('Laravel\Passport\ClientRepository');
        $client = m::mock('Laravel\Passport\Client');
        $clients->shouldReceive('findForUser')->with(1, 1)->andReturn($client);

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);

        $request->setUserResolver(function () {
            $user = m::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $clients->shouldReceive('update')->once()->with(
            m::type('Laravel\Passport\Client'), 'client name', 'http://localhost'
        )->andReturn('response');

        $redirectRule = m::mock(RedirectRule::class);

        $validator = m::mock('Illuminate\Contracts\Validation\Factory');
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
        $clients = m::mock('Laravel\Passport\ClientRepository');
        $clients->shouldReceive('findForUser')->with(1, 1)->andReturnNull();

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);

        $request->setUserResolver(function () {
            $user = m::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $clients->shouldReceive('update')->never();

        $validator = m::mock('Illuminate\Contracts\Validation\Factory');

        $controller = new ClientController(
            $clients, $validator, m::mock(RedirectRule::class)
        );

        $this->assertEquals(404, $controller->update($request, 1)->status());
    }

    public function test_clients_can_be_deleted()
    {
        $clients = m::mock('Laravel\Passport\ClientRepository');
        $client = m::mock('Laravel\Passport\Client');
        $clients->shouldReceive('findForUser')->with(1, 1)->andReturn($client);

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);

        $request->setUserResolver(function () {
            $user = m::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $clients->shouldReceive('delete')->once()->with(
            m::type('Laravel\Passport\Client')
        )->andReturn('response');

        $validator = m::mock('Illuminate\Contracts\Validation\Factory');

        $controller = new ClientController(
            $clients, $validator, m::mock(RedirectRule::class)
        );

        $controller->destroy($request, 1);
    }

    public function test_404_response_if_client_doesnt_belong_to_user_on_delete()
    {
        $clients = m::mock('Laravel\Passport\ClientRepository');
        $clients->shouldReceive('findForUser')->with(1, 1)->andReturnNull();

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);

        $request->setUserResolver(function () {
            $user = m::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $clients->shouldReceive('delete')->never();

        $validator = m::mock('Illuminate\Contracts\Validation\Factory');

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
