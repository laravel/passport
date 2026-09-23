<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use Laravel\Passport\Client;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\Http\Controllers\PersonalAccessTokenController;
use Laravel\Passport\Passport;
use Laravel\Passport\PersonalAccessTokenResult;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class PersonalAccessTokenControllerTest extends TestCase
{
    use VerifiesDoubles;

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

        $tokenRepository = Double::for(TokenRepository::class);
        $tokenRepository->allows('forUser')->returns($userTokens);

        $request->setUserResolver(function () {
            $user = Double::for(Authenticatable::class);
            $user->allows('getAuthIdentifier')->returns(1);

            return $user;
        });

        $validator = Double::for(Factory::class);
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

        $result = Double::for(PersonalAccessTokenResult::class);

        $request = Request::create('/', 'GET', ['name' => 'token name', 'scopes' => ['user', 'user-admin']]);

        $request->setUserResolver(function () use ($result) {
            $user = Double::for(OAuthenticatable::class);
            $user->expects('createToken')->with('token name', ['user', 'user-admin'])->returns($result);

            return $user;
        });

        $validator = Double::for(Factory::class);
        $validator->expects('make')->with([
            'name' => 'token name',
            'scopes' => ['user', 'user-admin'],
        ], [
            'name' => ['required', 'max:255'],
            'scopes' => ['array', Rule::in(Passport::scopeIds())],
        ])->returns($validation = Double::for(Validator::class));
        $validation->expects('validate');

        $tokenRepository = Double::for(TokenRepository::class);
        $controller = new PersonalAccessTokenController($tokenRepository, $validator);

        $this->assertSame($result, $controller->store($request));
    }

    public function test_tokens_can_be_deleted()
    {
        $request = Request::create('/', 'GET');

        $token1 = Double::for(Token::class)->passthru();
        $token1->id = 1;
        $token1->expects('revoke');

        $tokenRepository = Double::for(TokenRepository::class);
        $tokenRepository->allows('findForUser')->returns($token1);

        $request->setUserResolver(function () {
            $user = Double::for(Authenticatable::class);
            $user->allows('getAuthIdentifier')->returns(1);

            return $user;
        });

        $validator = Double::for(Factory::class);
        $controller = new PersonalAccessTokenController($tokenRepository, $validator);

        $response = $controller->destroy($request, 1);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->status());
    }

    public function test_not_found_response_is_returned_if_user_doesnt_have_token()
    {
        $user = Double::for(Authenticatable::class);
        $user->allows('getAuthIdentifier')->returns(1);

        $tokenRepository = Double::for(TokenRepository::class);
        $tokenRepository->allows('findForUser')->with(3, $user)->returns(null);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $user);

        $validator = Double::for(Factory::class);
        $controller = new PersonalAccessTokenController($tokenRepository, $validator);

        $this->assertSame(404, $controller->destroy($request, 3)->status());
    }
}
