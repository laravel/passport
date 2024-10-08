<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\Request;
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
        $clientRepository = m::mock(ClientRepository::class);
        $clientRepository->shouldReceive('forUser')->once()->with(1)
            ->andReturn($clients = (new Client)->newCollection());

        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->andReturn(1);

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
        $clients = m::mock(ClientRepository::class);
        $user = m::mock(Authenticatable::class);
        $user->shouldReceive('getAuthIdentifier')->andReturn(1);

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);
        $request->setUserResolver(fn () => $user);

        $clients->shouldReceive('createAuthorizationCodeGrantClient')
            ->once()
            ->with('client name', ['http://localhost'], true, $user)
            ->andReturn($client = new Client);

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
            ->andReturn($client = new Client);

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
    }

    public function test_clients_can_be_updated()
    {
        $clients = m::mock(ClientRepository::class);
        $client = m::mock(Client::class);
        $clients->shouldReceive('findForUser')->with(1, 1)->andReturn($client);

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);

        $request->setUserResolver(function () {
            $user = m::mock(Authenticatable::class);
            $user->shouldReceive('getAuthIdentifier')->andReturn(1);

            return $user;
        });

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
        $clients = m::mock(ClientRepository::class);
        $clients->shouldReceive('findForUser')->with(1, 1)->andReturnNull();

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);

        $request->setUserResolver(function () {
            $user = m::mock(Authenticatable::class);
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
            $user = m::mock(Authenticatable::class);
            $user->shouldReceive('getAuthIdentifier')->andReturn(1);

            return $user;
        });

        $clients->shouldReceive('revoke')->once()->with(
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
            $user = m::mock(Authenticatable::class);
            $user->shouldReceive('getAuthIdentifier')->andReturn(1);

            return $user;
        });

        $clients->shouldReceive('revoke')->never();

        $validator = m::mock(Factory::class);

        $controller = new ClientController(
            $clients, $validator, m::mock(RedirectRule::class)
        );

        $this->assertSame(404, $controller->destroy($request, 1)->status());
    }
}
