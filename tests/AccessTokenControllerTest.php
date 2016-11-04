<?php

use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;

class AccessTokenControllerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function test_a_token_can_be_issued()
    {
        $server = Mockery::mock('League\OAuth2\Server\AuthorizationServer');
        $tokens = Mockery::mock(Laravel\Passport\TokenRepository::class);

        $server->shouldReceive('respondToAccessTokenRequest')->with(
            Mockery::type('Psr\Http\Message\ServerRequestInterface'), Mockery::type('Psr\Http\Message\ResponseInterface')
        )->andReturn($response = new AccessTokenControllerTestStubResponse);

        $jwt = Mockery::mock(Lcobucci\JWT\Parser::class);
        // $jwt->shouldReceive('parse->getClaim')->andReturn('token-id');

        // $tokens->shouldReceive('find')->once()->with('token-id')->andReturn(new AccessTokenControllerTestStubToken);
        // $tokens->shouldReceive('revokeOtherAccessTokens')->once()->with(1, 2, 'token-id', false);

        $controller = new Laravel\Passport\Http\Controllers\AccessTokenController($server, $tokens, $jwt);

        $this->assertEquals($response, $controller->issueToken(Mockery::mock('Psr\Http\Message\ServerRequestInterface')));
    }

    public function test_exceptions_are_handled()
    {
        $container = new Container;
        Container::setInstance($container);
        $container->instance(ExceptionHandler::class, $exceptions = Mockery::mock());
        $exceptions->shouldReceive('report')->once();

        $tokens = Mockery::mock(Laravel\Passport\TokenRepository::class);
        $jwt = Mockery::mock(Lcobucci\JWT\Parser::class);

        $server = Mockery::mock('League\OAuth2\Server\AuthorizationServer');
        $server->shouldReceive('respondToAccessTokenRequest')->with(
            Mockery::type('Psr\Http\Message\ServerRequestInterface'), Mockery::type('Psr\Http\Message\ResponseInterface')
        )->andReturnUsing(function () { throw new Exception('whoops'); });

        $controller = new Laravel\Passport\Http\Controllers\AccessTokenController($server, $tokens, $jwt);

        $this->assertEquals('whoops', $controller->issueToken(Mockery::mock('Psr\Http\Message\ServerRequestInterface'))->getOriginalContent());
    }
}

class AccessTokenControllerTestStubResponse
{
    public function getStatusCode()
    {
        return 200;
    }

    public function getBody()
    {
        return $this;
    }

    public function __toString()
    {
        return json_encode(['access_token' => 'access-token']);
    }
}

class AccessTokenControllerTestStubToken
{
    public $client_id = 1;
    public $user_id = 2;
}
