<?php

namespace Laravel\Passport\Tests;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Passport\Http\Controllers\PersonalAccessTokenController;
use Laravel\Passport\Http\Resources\PersonalAccessTokenResource;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class PersonalAccessTokenControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $configMock = m::mock(ConfigRepository::class)
            ->shouldReceive('get')
            ->with('passport.json_resource_wrapper', null)
            ->andReturn('data')->getMock();

        app()->instance('config', $configMock);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function test_that_the_json_resource_wraps_in_data()
    {
        $this->assertEquals('data', PersonalAccessTokenResource::$wrap);
    }

    public function test_tokens_can_be_retrieved_for_users()
    {
        $request = Request::create('/', 'GET');

        $token1 = new Token;
        $token2 = new Token;
        $token1->client = (object) ['personal_access_client' => true];
        $token2->client = (object) ['personal_access_client' => false];

        $userTokens = m::mock(collect([$token1, $token2]));
        $userTokens->shouldReceive('load')->with('client')->andReturn(collect([
            $token1, $token2,
        ]));

        $tokenRepository = m::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('forUser')->andReturn($userTokens);

        $request->setUserResolver(function () use ($token1, $token2) {
            $user = m::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $validator = m::mock(Factory::class);
        $controller = new PersonalAccessTokenController($tokenRepository, $validator);

        $this->assertInstanceOf(JsonResource::class, $controller->forUser($request));
        $this->assertCount(1, $controller->forUser($request)->collection);
        $this->assertInstanceOf(PersonalAccessTokenResource::class, $controller->forUser($request)->collection[0]);
        $this->assertEquals($token1, $controller->forUser($request)->collection[0]->resource);
    }

    public function test_tokens_can_be_updated()
    {
        Passport::tokensCan([
            'user' => 'first',
            'user-admin' => 'second',
        ]);

        $request = Request::create('/', 'GET', ['name' => 'token name', 'scopes' => ['user', 'user-admin']]);

        $request->setUserResolver(function () {
            $user = m::mock();
            $user->shouldReceive('createToken')
                ->once()
                ->with('token name', ['user', 'user-admin'])
                ->andReturn('response');

            return $user;
        });

        $validator = m::mock(Factory::class);
        $validator->shouldReceive('make')->with([
            'name' => 'token name',
            'scopes' => ['user', 'user-admin'],
        ], [
            'name' => 'required|max:255',
            'scopes' => 'array|in:'.implode(',', Passport::scopeIds()),
        ])->andReturn($validator);
        $validator->shouldReceive('validate');

        $tokenRepository = m::mock(TokenRepository::class);
        $controller = new PersonalAccessTokenController($tokenRepository, $validator);

        $this->assertInstanceOf(PersonalAccessTokenResource::class, $controller->store($request));
        $this->assertEquals('response', $controller->store($request)->resource);
    }

    public function test_tokens_can_be_deleted()
    {
        $request = Request::create('/', 'GET');

        $token1 = m::mock(Token::class.'[revoke]');
        $token1->id = 1;
        $token1->shouldReceive('revoke')->once();

        $tokenRepository = m::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('findForUser')->andReturn($token1);

        $request->setUserResolver(function () {
            $user = m::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $validator = m::mock(Factory::class);
        $controller = new PersonalAccessTokenController($tokenRepository, $validator);

        $response = $controller->destroy($request, 1);

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->status());
    }

    public function test_not_found_response_is_returned_if_user_doesnt_have_token()
    {
        $request = Request::create('/', 'GET');

        $tokenRepository = m::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('findForUser')->with(3, 1)->andReturnNull();

        $request->setUserResolver(function () {
            $user = m::mock();
            $user->shouldReceive('getKey')->andReturn(1);

            return $user;
        });

        $validator = m::mock(Factory::class);
        $controller = new PersonalAccessTokenController($tokenRepository, $validator);

        $this->assertEquals(404, $controller->destroy($request, 3)->status());
    }
}
