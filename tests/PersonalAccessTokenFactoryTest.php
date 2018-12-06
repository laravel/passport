<?php

namespace Laravel\Passport\Tests;

use Mockery as m;
use Laravel\Passport\Token;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\PersonalAccessTokenFactory;

class PersonalAccessTokenFactoryTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_access_token_can_be_created()
    {
        $server = m::mock('League\OAuth2\Server\AuthorizationServer');
        $clients = m::mock('Laravel\Passport\ClientRepository');
        $tokens = m::mock('Laravel\Passport\TokenRepository');
        $jwt = m::mock('Lcobucci\JWT\Parser');

        $factory = new PersonalAccessTokenFactory($server, $clients, $tokens, $jwt);

        $clients->shouldReceive('personalAccessClient')->andReturn($client = new PersonalAccessTokenFactoryTestClientStub);
        $server->shouldReceive('respondToAccessTokenRequest')->andReturn($response = m::mock());
        $response->shouldReceive('getBody->__toString')->andReturn(json_encode([
            'access_token' => 'foo',
        ]));

        $jwt->shouldReceive('parse')->with('foo')->andReturn($parsedToken = m::mock());
        $parsedToken->shouldReceive('getClaim')->with('jti')->andReturn('token');
        $tokens->shouldReceive('find')
            ->with('token')
            ->andReturn($foundToken = new PersonalAccessTokenFactoryTestModelStub);
        $tokens->shouldReceive('save')->with($foundToken);

        $result = $factory->make(1, 'token', ['scopes']);

        $this->assertInstanceOf('Laravel\Passport\PersonalAccessTokenResult', $result);
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
