<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Http\Controllers\ClientController;
use Laravel\Passport\Http\Rules\RedirectRule;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ClientControllerTest extends TestCase
{
    protected function tearDown(): void
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
            ->with(1, 'client name', 'http://localhost', null, false, false, true)
            ->andReturn($client = new Client);

        $redirectRule = m::mock(RedirectRule::class);

        $validator = m::mock(Factory::class);
        $validator->shouldReceive('make')->once()->with([
            'name' => 'client name',
            'redirect' => 'http://localhost',
        ], [
            'name' => 'required|max:191',
            'redirect' => ['required', $redirectRule],
            'confidential' => 'boolean',
        ])->andReturn($validator);
        $validator->shouldReceive('validate')->once();

        $controller = new ClientController(
            $clients, $validator, $redirectRule
        );

        $this->assertEquals($client, $controller->store($request));
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
            ->with(1, 'client name', 'http://localhost', null, false, false, false)
            ->andReturn($client = new Client);

        $redirectRule = m::mock(RedirectRule::class);

        $validator = m::mock(Factory::class);
        $validator->shouldReceive('make')->once()->with([
            'name' => 'client name',
            'redirect' => 'http://localhost',
            'confidential' => false,
        ], [
            'name' => 'required|max:191',
            'redirect' => ['required', $redirectRule],
            'confidential' => 'boolean',
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
            $user->shouldReceive('getAuthIdentifier')->andReturn(1);

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
            'name' => 'required|max:191',
            'redirect' => ['required', $redirectRule],
        ])->andReturn($validator);
        $validator->shouldReceive('validate')->once();

        $controller = new ClientController(
            $clients, $validator, $redirectRule
        );

        $this->assertSame('response', $controller->update($request, 1));
    }

    public function test_clients_secret_can_be_regenerated_for_a_valid_client_and_old_secret()
    {
        $request = Request::create('/clients/1/generate-secret', 'PUT', ['old_secret' => 'abcd1234']);

        $request->setUserResolver(function () {
            $user = m::mock();
            $user->shouldReceive('getAuthIdentifier')->andReturn(1);

            return $user;
        });

        $client = m::mock(Client::class, ['secret' => 'abcd1234']);
        $clients = m::mock(ClientRepository::class);

        // returns incorrect old secret first and then correct one.
        $client->shouldReceive('getAttribute')->with('secret')->andReturn('incorrect', 'abcd1234');

        // Returns null first simulating invalid client.
        $clients->shouldReceive('findForUser')->with(1, 1)->andReturn(null, $client, $client);

        $clients->shouldReceive('regenerateSecret')->once()->with($client)->andReturn($client = new Client());

        $redirectRule = m::mock(RedirectRule::class);

        $validator = m::mock(Factory::class);

        $validator->shouldReceive('make')->twice()->with([
            'old_secret' => 'abcd1234',
        ], [
            'old_secret' => 'required',
        ])->andReturn($validator);

        $validator->shouldReceive('validate')->twice();

        $controller = new ClientController(
            $clients, $validator, $redirectRule
        );

        $response = $controller->generateSecret($request, 1);

        // Expect first invocation to return 404 when the specified client is invalid
        // or does not belong to the user.
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(404, $response->getStatusCode());

        $response = $controller->generateSecret($request, 1);

        // Expect second invocation to return 401 when the provided old secret is
        // incorrect.
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(401, $response->getStatusCode());

        // Expect third invocation to succeed
        $this->assertEquals($client, $controller->generateSecret($request, 1));
    }

    public function test_404_response_if_client_doesnt_belong_to_user()
    {
        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('findForUser')->with(1, 1)->andReturnNull();

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);

        $request->setUserResolver(function () {
            $user = m::mock();
            $user->shouldReceive('getAuthIdentifier')->andReturn(1);

            return $user;
        });

        $clients->shouldReceive('update')->never();

        $validator = m::mock(Factory::class);

        $controller = new ClientController(
            $clients, $validator, m::mock(RedirectRule::class)
        );

        $this->assertSame(404, $controller->update($request, 1)->status());
    }

    public function test_clients_can_be_deleted()
    {
        $clients = m::mock(ClientRepository::class);
        $client = m::mock(Client::class);
        $clients->shouldReceive('findForUser')->with(1, 1)->andReturn($client);

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);

        $request->setUserResolver(function () {
            $user = m::mock();
            $user->shouldReceive('getAuthIdentifier')->andReturn(1);

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

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->status());
    }

    public function test_404_response_if_client_doesnt_belong_to_user_on_delete()
    {
        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('findForUser')->with(1, 1)->andReturnNull();

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);

        $request->setUserResolver(function () {
            $user = m::mock();
            $user->shouldReceive('getAuthIdentifier')->andReturn(1);

            return $user;
        });

        $clients->shouldReceive('delete')->never();

        $validator = m::mock(Factory::class);

        $controller = new ClientController(
            $clients, $validator, m::mock(RedirectRule::class)
        );

        $this->assertSame(404, $controller->destroy($request, 1)->status());
    }
}

class ClientControllerFakeUser
{
    public $id = 1;

    public function getAuthIdentifier()
    {
        return $this->id;
    }
}
