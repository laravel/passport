<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Passport\Client;
use Laravel\Passport\Http\Controllers\PersonalAccessTokenController;
use Laravel\Passport\Passport;
use Laravel\Passport\PersonalAccessTokenResult;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class PersonalAccessTokenControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_tokens_can_be_retrieved_for_users()
    {
        $request = Request::create('/', 'GET');

        $token1 = new Token;
        $token2 = new Token;
        $token1->client = new Client(['grant_types' => ['personal_access']]);
        $token2->client = new Client(['grant_types' => []]);
        $userTokens = (new Token)->newCollection([
            $token1, $token2,
        ]);

        $tokenRepository = m::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('forUser')->andReturn($userTokens);

        $request->setUserResolver(function () {
            $user = m::mock(Authenticatable::class);
            $user->shouldReceive('getAuthIdentifier')->andReturn(1);

            return $user;
        });

        $validator = m::mock(Factory::class);
        $controller = new PersonalAccessTokenController($tokenRepository, $validator);

        $this->assertCount(1, $controller->forUser($request));
        $this->assertEquals($token1, $controller->forUser($request)[0]);
    }

    public function test_tokens_can_be_updated()
    {
        Passport::tokensCan([
            'user' => 'first',
            'user-admin' => 'second',
        ]);

        $result = m::mock(PersonalAccessTokenResult::class);

        $request = Request::create('/', 'GET', ['name' => 'token name', 'scopes' => ['user', 'user-admin']]);

        $request->setUserResolver(function () use ($result) {
            $user = m::mock(Authenticatable::class);
            $user->shouldReceive('createToken')
                ->once()
                ->with('token name', ['user', 'user-admin'])
                ->andReturn($result);

            return $user;
        });

        $validator = m::mock(Factory::class);
        $validator->shouldReceive('make')->once()->with([
            'name' => 'token name',
            'scopes' => ['user', 'user-admin'],
        ], [
            'name' => ['required', 'max:255'],
            'scopes' => ['array', Rule::in(Passport::scopeIds())],
        ])->andReturn($validator);
        $validator->shouldReceive('validate')->once();

        $tokenRepository = m::mock(TokenRepository::class);
        $controller = new PersonalAccessTokenController($tokenRepository, $validator);

        $this->assertSame($result, $controller->store($request));
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
            $user = m::mock(Authenticatable::class);
            $user->shouldReceive('getAuthIdentifier')->andReturn(1);

            return $user;
        });

        $validator = m::mock(Factory::class);
        $controller = new PersonalAccessTokenController($tokenRepository, $validator);

        $response = $controller->destroy($request, 1);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->status());
    }

    public function test_not_found_response_is_returned_if_user_doesnt_have_token()
    {
        $request = Request::create('/', 'GET');

        $tokenRepository = m::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('findForUser')->with(3, 1)->andReturnNull();

        $request->setUserResolver(function () {
            $user = m::mock(Authenticatable::class);
            $user->shouldReceive('getAuthIdentifier')->andReturn(1);

            return $user;
        });

        $validator = m::mock(Factory::class);
        $controller = new PersonalAccessTokenController($tokenRepository, $validator);

        $this->assertSame(404, $controller->destroy($request, 3)->status());
    }
}
