<?php

use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Passport\Http\Middleware\AttachesPasswordGrantCredentials;

class AttachesPasswordGrantCredentialsTest extends PHPUnit_Framework_TestCase
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

        $middleware = new AttachesPasswordGrantCredentials($clientRepository);

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

        $middleware = new AttachesPasswordGrantCredentials($clientRepository);

        $request = Request::create('/', 'POST', ['grant_type' => 'password']);

        $middleware->handle($request, function () {
            return 'response';
        });
    }
}
