<?php

use Illuminate\Http\Request;
use Laravel\Passport\Passport;
use Laravel\Passport\TokenRepository;

class PersonalAccessTokenControllerTest extends PHPUnit_Framework_TestCase
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

        $userTokens = Mockery::mock();
        $token1->client = (object) ['personal_access_client' => true];
        $token2->client = (object) ['personal_access_client' => false];
        $userTokens->shouldReceive('load')->with('client')->andReturn(collect([
            $token1, $token2,
        ]));

        $tokenRepository = Mockery::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('forUser')->andReturn($userTokens);

        $request->setUserResolver(function () use ($token1, $token2) {
            $user = Mockery::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $validator = Mockery::mock('Illuminate\Contracts\Validation\Factory');
        $controller = new Laravel\Passport\Http\Controllers\PersonalAccessTokenController($tokenRepository, $validator);

        $this->assertEquals(1, count($controller->forUser($request)));
        $this->assertEquals($token1, $controller->forUser($request)[0]);
    }

    public function test_tokens_can_be_updated()
    {
        Passport::tokensCan([
            'user' => 'first',
            'user-admin' => 'second',
        ]);

        $request = Request::create('/', 'GET', ['name' => 'token name', 'scopes' => ['user', 'user-admin']]);

        $request->setUserResolver(function () {
            $user = Mockery::mock();
            $user->shouldReceive('createToken')->once()->with('token name', ['user', 'user-admin'])->andReturn('response');

            return $user;
        });

        $validator = Mockery::mock('Illuminate\Contracts\Validation\Factory');
        $validator->shouldReceive('make')->once()->with([
            'name' => 'token name',
            'scopes' => ['user', 'user-admin'],
        ], [
            'name' => 'required|max:255',
            'scopes' => 'array|in:'.implode(',', Passport::scopeIds()),
        ])->andReturn($validator);
        $validator->shouldReceive('validate')->once();

        $tokenRepository = Mockery::mock(TokenRepository::class);
        $controller = new Laravel\Passport\Http\Controllers\PersonalAccessTokenController($tokenRepository, $validator);

        $this->assertEquals('response', $controller->store($request));
    }

    public function test_tokens_can_be_deleted()
    {
        $request = Request::create('/', 'GET');

        $token1 = Mockery::mock(Laravel\Passport\Token::class.'[revoke]');
        $token1->id = 1;
        $token1->shouldReceive('revoke')->once();

        $tokenRepository = Mockery::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('findForUser')->andReturn($token1);

        $request->setUserResolver(function () {
            $user = Mockery::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $validator = Mockery::mock('Illuminate\Contracts\Validation\Factory');
        $controller = new Laravel\Passport\Http\Controllers\PersonalAccessTokenController($tokenRepository, $validator);

        $controller->destroy($request, 1);
    }

    public function test_not_found_response_is_returned_if_user_doesnt_have_token()
    {
        $request = Request::create('/', 'GET');

        $tokenRepository = Mockery::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('findForUser')->with(3, 1)->andReturnNull();

        $request->setUserResolver(function () {
            $user = Mockery::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $validator = Mockery::mock('Illuminate\Contracts\Validation\Factory');
        $controller = new Laravel\Passport\Http\Controllers\PersonalAccessTokenController($tokenRepository, $validator);

        $this->assertEquals(404, $controller->destroy($request, 3)->status());
    }
}
