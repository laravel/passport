<?php

namespace Laravel\Passport\Tests\Unit;

use Laravel\Passport\ClientRepository;
use Laravel\Passport\PersonalAccessTokenFactory;
use Laravel\Passport\PersonalAccessTokenResult;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\Token\Plain as PlainToken;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\Token\Signature;
use League\OAuth2\Server\AuthorizationServer;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class PersonalAccessTokenFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_access_token_can_be_created()
    {
        $server = m::mock(AuthorizationServer::class);
        $clients = m::mock(ClientRepository::class);
        $tokens = m::mock(TokenRepository::class);
        $jwt = m::mock(Parser::class);

        $factory = new PersonalAccessTokenFactory($server, $clients, $tokens, $jwt);

        $clients->shouldReceive('personalAccessClient')->andReturn($client = new PersonalAccessTokenFactoryTestClientStub);
        $server->shouldReceive('respondToAccessTokenRequest')->andReturn($response = m::mock());
        $response->shouldReceive('getBody->__toString')->andReturn(json_encode([
            'access_token' => 'foo',
        ]));

        $parsedToken = new PlainToken(
            new DataSet([], ''),
            new DataSet([RegisteredClaims::ID => 'token'], ''),
            Signature::fromEmptyData()
        );

        $jwt->shouldReceive('parse')->with('foo')->andReturn($parsedToken);
        $tokens->shouldReceive('find')
            ->with('token')
            ->andReturn($foundToken = new PersonalAccessTokenFactoryTestModelStub);
        $tokens->shouldReceive('save')->with($foundToken);

        $result = $factory->make(1, 'token', ['scopes']);

        $this->assertInstanceOf(PersonalAccessTokenResult::class, $result);
    }
}

class PersonalAccessTokenFactoryTestClientStub
{
    public $id = 1;

    public $secret = 'something';
}

class PersonalAccessTokenFactoryTestModelStub extends Token
{
    public $id = 1;

    public $secret = 'something';
}
