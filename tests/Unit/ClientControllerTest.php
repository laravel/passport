<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Http\Controllers\ClientController;
use Laravel\Passport\Http\Rules\RedirectRule;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ClientControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_all_the_clients_for_the_current_user_can_be_retrieved()
    {
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->andReturn(1);

        $clientRepository = m::mock(ClientRepository::class);
        $clientRepository->shouldReceive('forUser')->once()->with($user)
            ->andReturn($clients = (new Client)->newCollection());

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new ClientController(
            $clientRepository,
            m::mock(Factory::class),
            m::mock(RedirectRule::class)
        );

        $this->assertEquals($clients, $controller->forUser($request));
    }

    public function test_clients_can_be_stored()
    {
        Hash::expects('isHashed')->once()->with('secret')->andReturn(false);
        Hash::expects('make')->once()->with('secret')->andReturn('hashed_secret');

        $clients = m::mock(ClientRepository::class);
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->andReturn(1);

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);
        $request->setUserResolver(fn () => $user);

        $clients->shouldReceive('createAuthorizationCodeGrantClient')
            ->once()
            ->with('client name', ['http://localhost'], true, $user)
            ->andReturn($client = new Client([
                'name' => 'client name',
                'redirect' => 'http://localhost',
                'secret' => 'secret',
            ]));

        $redirectRule = m::mock(RedirectRule::class);

        $validator = m::mock(Factory::class);
        $validator->shouldReceive('make')->once()->with([
            'name' => 'client name',
            'redirect' => 'http://localhost',
        ], [
            'name' => ['required', 'string', 'max:255'],
            'redirect' => ['required', $redirectRule],
            'confidential' => 'boolean',
        ])->andReturn($validator);
        $validator->shouldReceive('validate')->once();

        $controller = new ClientController(
            $clients, $validator, $redirectRule
        );

        $this->assertEquals($client, $controller->store($request));
        $this->assertSame('hashed_secret', $client->secret);
        $this->assertSame([
            'name' => 'client name',
            'redirect' => 'http://localhost',
            'plain_secret' => 'secret',
        ], $client->toArray());
    }

    public function test_public_clients_can_be_stored()
    {
        $clients = m::mock(ClientRepository::class);
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->andReturn(1);

        $request = Request::create(
            '/',
            'GET',
            ['name' => 'client name', 'redirect' => 'http://localhost', 'confidential' => false]
        );
        $request->setUserResolver(fn () => $user);

        $clients->shouldReceive('createAuthorizationCodeGrantClient')
            ->once()
            ->with('client name', ['http://localhost'], false, $user)
            ->andReturn($client = new Client([
                'name' => 'client name',
                'redirect' => 'http://localhost',
                'secret' => null,
            ]));

        $redirectRule = m::mock(RedirectRule::class);

        $validator = m::mock(Factory::class);
        $validator->shouldReceive('make')->once()->with([
            'name' => 'client name',
            'redirect' => 'http://localhost',
            'confidential' => false,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'redirect' => ['required', $redirectRule],
            'confidential' => 'boolean',
        ])->andReturn($validator);
        $validator->shouldReceive('validate')->once();

        $controller = new ClientController(
            $clients, $validator, $redirectRule
        );

        $this->assertEquals($client, $controller->store($request));
        $this->assertNull($client->secret);
        $this->assertSame([
            'name' => 'client name',
            'redirect' => 'http://localhost',
        ], $client->toArray());
    }

    public function test_clients_can_be_updated()
    {
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->andReturn(1);

        $clients = m::mock(ClientRepository::class);
        $client = m::mock(Client::class);
        $clients->shouldReceive('findForUser')->with(1, $user)->andReturn($client);

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);
        $request->setUserResolver(fn () => $user);

        $clients->shouldReceive('update')->once()->with(
            $client, 'client name', ['http://localhost']
        )->andReturn(true);

        $redirectRule = m::mock(RedirectRule::class);

        $validator = m::mock(Factory::class);
        $validator->shouldReceive('make')->once()->with([
            'name' => 'client name',
            'redirect' => 'http://localhost',
        ], [
            'name' => ['required', 'string', 'max:255'],
            'redirect' => ['required', $redirectRule],
        ])->andReturn($validator);
        $validator->shouldReceive('validate')->once();

        $controller = new ClientController(
            $clients, $validator, $redirectRule
        );

        $this->assertSame($client, $controller->update($request, 1));
    }

    public function test_404_response_if_client_doesnt_belong_to_user()
    {
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->andReturn(1);

        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('findForUser')->with(1, $user)->andReturnNull();

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);
        $request->setUserResolver(fn () => $user);

        $clients->shouldReceive('update')->never();

        $validator = m::mock(Factory::class);

        $controller = new ClientController(
            $clients, $validator, m::mock(RedirectRule::class)
        );

        $this->assertSame(404, $controller->update($request, 1)->status());
    }

    public function test_clients_can_be_deleted()
    {
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->andReturn(1);

        $clients = m::mock(ClientRepository::class);
        $client = m::mock(Client::class);
        $clients->shouldReceive('findForUser')->with(1, $user)->andReturn($client);

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);
        $request->setUserResolver(fn () => $user);

        $clients->shouldReceive('delete')->once()->with(
            m::type(Client::class)
        );

        $validator = m::mock(Factory::class);

        $controller = new ClientController(
            $clients, $validator, m::mock(RedirectRule::class)
        );

        $response = $controller->destroy($request, 1);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->status());
    }

    public function test_404_response_if_client_doesnt_belong_to_user_on_delete()
    {
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->andReturn(1);

        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('findForUser')->with(1, $user)->andReturnNull();

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);
        $request->setUserResolver(fn () => $user);

        $clients->shouldReceive('delete')->never();

        $validator = m::mock(Factory::class);

        $controller = new ClientController(
            $clients, $validator, m::mock(RedirectRule::class)
        );

        $this->assertSame(404, $controller->destroy($request, 1)->status());
    }
}
