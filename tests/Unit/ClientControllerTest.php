<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Matching\Argument;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Http\Controllers\ClientController;
use Laravel\Passport\Http\Rules\RedirectRule;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ClientControllerTest extends TestCase
{
    use VerifiesDoubles;

    public function test_all_the_clients_for_the_current_user_can_be_retrieved()
    {
        $user = Double::for(Authenticatable::class);
        $user->allows('getAuthIdentifier')->returns(1);

        $clientRepository = Double::for(ClientRepository::class);
        $clientRepository->expects('forUser')->with($user)->returns($clients = (new Client)->newCollection());

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new ClientController(
            $clientRepository,
            Double::for(Factory::class),
            Double::for(RedirectRule::class)
        );

        $this->assertEquals($clients, $controller->forUser($request));
    }

    public function test_clients_can_be_stored()
    {
        Hash::expects('isHashed')->once()->with('secret')->andReturn(false);
        Hash::expects('make')->once()->with('secret')->andReturn('hashed_secret');

        $clients = Double::for(ClientRepository::class);
        $user = Double::for(Authenticatable::class);
        $user->allows('getAuthIdentifier')->returns(1);

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);
        $request->setUserResolver(fn () => $user);

        $clients->expects('createAuthorizationCodeGrantClient')->with('client name', ['http://localhost'], true, $user)->returns($client = new Client([
            'name' => 'client name',
            'redirect' => 'http://localhost',
            'secret' => 'secret',
        ]));

        $redirectRule = Double::for(RedirectRule::class);

        $validator = Double::for(Factory::class);
        $validator->expects('make')->with([
            'name' => 'client name',
            'redirect' => 'http://localhost',
        ], [
            'name' => ['required', 'string', 'max:255'],
            'redirect' => ['required', $redirectRule],
            'confidential' => 'boolean',
        ])->returns($validation = Double::for(Validator::class));
        $validation->expects('validate');

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
        $clients = Double::for(ClientRepository::class);
        $user = Double::for(Authenticatable::class);
        $user->allows('getAuthIdentifier')->returns(1);

        $request = Request::create(
            '/',
            'GET',
            ['name' => 'client name', 'redirect' => 'http://localhost', 'confidential' => false]
        );
        $request->setUserResolver(fn () => $user);

        $clients->expects('createAuthorizationCodeGrantClient')->with('client name', ['http://localhost'], false, $user)->returns($client = new Client([
            'name' => 'client name',
            'redirect' => 'http://localhost',
            'secret' => null,
        ]));

        $redirectRule = Double::for(RedirectRule::class);

        $validator = Double::for(Factory::class);
        $validator->expects('make')->with([
            'name' => 'client name',
            'redirect' => 'http://localhost',
            'confidential' => false,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'redirect' => ['required', $redirectRule],
            'confidential' => 'boolean',
        ])->returns($validation = Double::for(Validator::class));
        $validation->expects('validate');

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
        $user = Double::for(Authenticatable::class);
        $user->allows('getAuthIdentifier')->returns(1);

        $clients = Double::for(ClientRepository::class);
        $client = Double::for(Client::class);
        $clients->allows('findForUser')->with(1, $user)->returns($client);

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);
        $request->setUserResolver(fn () => $user);

        $clients->expects('update')->with($client, 'client name', ['http://localhost'])->returns(true);

        $redirectRule = Double::for(RedirectRule::class);

        $validator = Double::for(Factory::class);
        $validator->expects('make')->with([
            'name' => 'client name',
            'redirect' => 'http://localhost',
        ], [
            'name' => ['required', 'string', 'max:255'],
            'redirect' => ['required', $redirectRule],
        ])->returns($validation = Double::for(Validator::class));
        $validation->expects('validate');

        $controller = new ClientController(
            $clients, $validator, $redirectRule
        );

        $this->assertSame($client, $controller->update($request, 1));
    }

    public function test_404_response_if_client_doesnt_belong_to_user()
    {
        $user = Double::for(Authenticatable::class);
        $user->allows('getAuthIdentifier')->returns(1);

        $clients = Double::for(ClientRepository::class);
        $clients->allows('findForUser')->with(1, $user)->returns(null);

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);
        $request->setUserResolver(fn () => $user);

        $clients->expects('update')->never();

        $validator = Double::for(Factory::class);

        $controller = new ClientController(
            $clients, $validator, Double::for(RedirectRule::class)
        );

        $this->assertSame(404, $controller->update($request, 1)->status());
    }

    public function test_clients_can_be_deleted()
    {
        $user = Double::for(Authenticatable::class);
        $user->allows('getAuthIdentifier')->returns(1);

        $clients = Double::for(ClientRepository::class);
        $client = Double::for(Client::class);
        $clients->allows('findForUser')->with(1, $user)->returns($client);

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);
        $request->setUserResolver(fn () => $user);

        $clients->expects('delete')->with(Argument::type(Client::class));

        $validator = Double::for(Factory::class);

        $controller = new ClientController(
            $clients, $validator, Double::for(RedirectRule::class)
        );

        $response = $controller->destroy($request, 1);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->status());
    }

    public function test_404_response_if_client_doesnt_belong_to_user_on_delete()
    {
        $user = Double::for(Authenticatable::class);
        $user->allows('getAuthIdentifier')->returns(1);

        $clients = Double::for(ClientRepository::class);
        $clients->allows('findForUser')->with(1, $user)->returns(null);

        $request = Request::create('/', 'GET', ['name' => 'client name', 'redirect' => 'http://localhost']);
        $request->setUserResolver(fn () => $user);

        $clients->expects('delete')->never();

        $validator = Double::for(Factory::class);

        $controller = new ClientController(
            $clients, $validator, Double::for(RedirectRule::class)
        );

        $this->assertSame(404, $controller->destroy($request, 1)->status());
    }
}
