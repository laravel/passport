<?php

use Illuminate\Http\Request;
use Laravel\Passport\Client;

class AuthorizedAccessTokenControllerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function test_tokens_can_be_retrieved_for_users()
    {
        $request = Request::create('/', 'GET');

        $token1 = new Laravel\Passport\Token;
        $token2 = new Laravel\Passport\Token;

        $request->setUserResolver(function () use ($token1, $token2) {
            $user = Mockery::mock();
            $user->id = 1;
            $user->tokens = Mockery::mock();
            $client1 = new Client;
            $client1->personal_access_client = true;
            $client2 = new Client;
            $client2->personal_access_client = false;
            $token1->client = $client1;
            $token2->client = $client2;
            $user->tokens->shouldReceive('load')->with('client')->andReturn(collect([
                $token1, $token2,
            ]));

            return $user;
        });

        $controller = new Laravel\Passport\Http\Controllers\AuthorizedAccessTokenController;

        $this->assertEquals(1, count($controller->forUser($request)));
        $this->assertEquals($token2, $controller->forUser($request)[0]);
    }

    public function test_tokens_can_be_deleted()
    {
        $request = Request::create('/', 'GET');

        $token1 = Mockery::mock(Laravel\Passport\Token::class.'[revoke]');
        $token1->id = 1;
        $token1->shouldReceive('revoke')->once();
        $token2 = Mockery::mock(Laravel\Passport\Token::class.'[revoke]');
        $token2->id = 2;
        $token2->shouldReceive('revoke')->never();

        $request->setUserResolver(function () use ($token1, $token2) {
            $user = Mockery::mock();
            $user->id = 1;
            $user->tokens = new Illuminate\Database\Eloquent\Collection([$token1, $token2]);

            return $user;
        });

        $validator = Mockery::mock('Illuminate\Contracts\Validation\Factory');
        $controller = new Laravel\Passport\Http\Controllers\AuthorizedAccessTokenController($validator);

        $controller->destroy($request, 1);
    }

    public function test_not_found_response_is_returned_if_user_doesnt_have_token()
    {
        $request = Request::create('/', 'GET');

        $token1 = Mockery::mock(Laravel\Passport\Token::class.'[revoke]');
        $token1->id = 1;
        $token1->shouldReceive('revoke')->never();
        $token2 = Mockery::mock(Laravel\Passport\Token::class.'[revoke]');
        $token2->id = 2;
        $token2->shouldReceive('revoke')->never();

        $request->setUserResolver(function () use ($token1, $token2) {
            $user = Mockery::mock();
            $user->id = 1;
            $user->tokens = new Illuminate\Database\Eloquent\Collection([$token1, $token2]);

            return $user;
        });

        $validator = Mockery::mock('Illuminate\Contracts\Validation\Factory');
        $controller = new Laravel\Passport\Http\Controllers\AuthorizedAccessTokenController($validator);

        $this->assertEquals(404, $controller->destroy($request, 3)->status());
    }
}
