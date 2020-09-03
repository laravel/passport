<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\PersonalAccessTokenController;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class PersonalAccessTokenControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_tokens_can_be_retrieved_for_users()
    {
        $request = Request::create('/', 'GET');

        $token1 = new Token;
        $token2 = new Token;

        $userTokens = m::mock();
        $token1->client = (object) ['personal_access_client' => true];
        $token2->client = (object) ['personal_access_client' => false];
        $userTokens->shouldReceive('load')->with('client')->andReturn(collect([
            $token1, $token2,
        ]));

        $tokenRepository = m::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('forUser')->andReturn($userTokens);

        $request->setUserResolver(function () {
            $user = m::mock();
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
        $validator->shouldReceive('make')->once()->with([
            'name' => 'token name',
            'scopes' => ['user', 'user-admin'],
        ], [
            'name' => 'required|max:191',
            'scopes' => 'array|in:'.implode(',', Passport::scopeIds()),
        ])->andReturn($validator);
        $validator->shouldReceive('validate')->once();

        $tokenRepository = m::mock(TokenRepository::class);
        $controller = new PersonalAccessTokenController($tokenRepository, $validator);

        $this->assertSame('response', $controller->store($request));
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
            $user = m::mock();
            $user->shouldReceive('getAuthIdentifier')->andReturn(1);

            return $user;
        });

        $validator = m::mock(Factory::class);
        $controller = new PersonalAccessTokenController($tokenRepository, $validator);

        $this->assertSame(404, $controller->destroy($request, 3)->status());
    }
}
