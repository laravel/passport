<?php

use Illuminate\Http\Request;

class ClientControllerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function test_all_the_clients_for_the_current_user_can_be_retrieved()
    {
        $clients = Mockery::mock('Laravel\Passport\ClientRepository');
        $clients->shouldReceive('activeForUser')->once()->with(1)->andReturn($client = Mockery::mock());
        $client->shouldReceive('makeVisible')->with('secret')->andReturn($client);

        $request = Mockery::mock('Illuminate\Http\Request');
        $request->shouldReceive('user')->andReturn(new ClientControllerFakeUser);

        $controller = new Laravel\Passport\Http\Controllers\ClientController(
            $clients, Mockery::mock('Illuminate\Contracts\Validation\Factory')
        );

        $this->assertEquals($client, $controller->forUser($request));
    }

    public function test_clients_can_be_stored()
    {
        $clients = Mockery::mock('Laravel\Passport\ClientRepository');

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);
        $request->setUserResolver(function () {
            return new ClientControllerFakeUser;
        });

        $clients->shouldReceive('create')->once()->with(1, 'client name', 'http://localhost')->andReturn($client = new Laravel\Passport\Client);

        $validator = Mockery::mock('Illuminate\Contracts\Validation\Factory');
        $validator->shouldReceive('make')->once()->with([
            'name' => 'client name',
            'redirect' => 'http://localhost',
        ], [
            'name' => 'required|max:255',
            'redirect' => 'required|url',
        ])->andReturn($validator);
        $validator->shouldReceive('validate')->once();

        $controller = new Laravel\Passport\Http\Controllers\ClientController(
            $clients, $validator
        );

        $this->assertEquals($client, $controller->store($request));
    }

    public function test_clients_can_be_updated()
    {
        $clients = Mockery::mock('Laravel\Passport\ClientRepository');
        $client = Mockery::mock('Laravel\Passport\Client');
        $clients->shouldReceive('findForUser')->with(1, 1)->andReturn($client);

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);

        $request->setUserResolver(function () {
            $user = Mockery::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $clients->shouldReceive('update')->once()->with(
            Mockery::type('Laravel\Passport\Client'), 'client name', 'http://localhost'
        )->andReturn('response');

        $validator = Mockery::mock('Illuminate\Contracts\Validation\Factory');
        $validator->shouldReceive('make')->once()->with([
            'name' => 'client name',
            'redirect' => 'http://localhost',
        ], [
            'name' => 'required|max:255',
            'redirect' => 'required|url',
        ])->andReturn($validator);
        $validator->shouldReceive('validate')->once();

        $controller = new Laravel\Passport\Http\Controllers\ClientController(
            $clients, $validator
        );

        $this->assertEquals('response', $controller->update($request, 1));
    }

    public function test_404_response_if_client_doesnt_belong_to_user()
    {
        $clients = Mockery::mock('Laravel\Passport\ClientRepository');
        $clients->shouldReceive('findForUser')->with(1, 1)->andReturnNull();

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);

        $request->setUserResolver(function () {
            $user = Mockery::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $clients->shouldReceive('update')->never();

        $validator = Mockery::mock('Illuminate\Contracts\Validation\Factory');

        $controller = new Laravel\Passport\Http\Controllers\ClientController(
            $clients, $validator
        );

        $this->assertEquals(404, $controller->update($request, 1)->status());
    }

    public function test_clients_can_be_deleted()
    {
        $clients = Mockery::mock('Laravel\Passport\ClientRepository');
        $client = Mockery::mock('Laravel\Passport\Client');
        $clients->shouldReceive('findForUser')->with(1, 1)->andReturn($client);

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);

        $request->setUserResolver(function () {
            $user = Mockery::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $clients->shouldReceive('delete')->once()->with(
            Mockery::type('Laravel\Passport\Client')
        )->andReturn('response');

        $validator = Mockery::mock('Illuminate\Contracts\Validation\Factory');

        $controller = new Laravel\Passport\Http\Controllers\ClientController(
            $clients, $validator
        );

        $controller->destroy($request, 1);
    }

    public function test_404_response_if_client_doesnt_belong_to_user_on_delete()
    {
        $clients = Mockery::mock('Laravel\Passport\ClientRepository');
        $clients->shouldReceive('findForUser')->with(1, 1)->andReturnNull();

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);

        $request->setUserResolver(function () {
            $user = Mockery::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $clients->shouldReceive('delete')->never();

        $validator = Mockery::mock('Illuminate\Contracts\Validation\Factory');

        $controller = new Laravel\Passport\Http\Controllers\ClientController(
            $clients, $validator
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
