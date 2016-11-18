<?php

use Laravel\Passport\Client;
use Illuminate\Http\Request;
use Illuminate\Encryption\Encrypter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Passport\Http\Middleware\HandlesGrantParameterInjections;

class HandlesGrantParameterInjectionsTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function test_request_is_passed_along_if_client_id_is_valid()
    {
        $clientRepository = Mockery::mock('Laravel\Passport\ClientRepository');
        $clientRepository->shouldReceive('find')->andReturn($client = Mockery::mock('Laravel\Passport\Client'));
        $client->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $client->shouldReceive('getAttribute')->with('secret')->andReturn('secret');
        $encrypter = new Encrypter(str_repeat('a', 16));

        $middleware = new HandlesGrantParameterInjections($clientRepository, $encrypter);

        $request = Request::create('/', 'POST', ['grant_type' => 'password', 'client_id' => 1]);

        $response = $middleware->handle($request, function () {
            return 'response';
        });

        $this->assertEquals('response', $response);
        $this->assertEquals('secret', $request->client_secret);
    }

    /**
     * @expectedException Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function test_exception_is_thrown_if_client_id_is_invalid()
    {
        $clientRepository = Mockery::mock('Laravel\Passport\ClientRepository');
        $clientRepository->shouldReceive('find')->andReturnUsing(function () {
            throw (new ModelNotFoundException())->setModel(Client::class);
        });
        $encrypter = new Encrypter(str_repeat('a', 16));

        $middleware = new HandlesGrantParameterInjections($clientRepository, $encrypter);

        $request = Request::create('/', 'POST', ['grant_type' => 'password']);

        $middleware->handle($request, function () {
            return 'response';
        });
    }

    public function test_request_is_passed_along_with_the_refresh_token_if_we_are_attempting_to_refresh_with_a_cookie()
    {
        $clientRepository = Mockery::mock('Laravel\Passport\ClientRepository');
        $encrypter = new Encrypter(str_repeat('a', 16));

        $middleware = new HandlesGrantParameterInjections($clientRepository, $encrypter);

        $request = Request::create('/', 'POST', ['grant_type' => 'refresh_token']);
        $request->cookies->set('laravel_token', $encrypter->encrypt(['refresh_token' => 'refresh']));

        $response = $middleware->handle($request, function () {
            return 'response';
        });

        $this->assertEquals('response', $response);
        $this->assertEquals('refresh', $request->refresh_token);

    }
}
