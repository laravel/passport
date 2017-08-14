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

        $psrResponse = new Zend\Diactoros\Response();
        $psrResponse->getBody()->write(json_encode(['access_token' => 'access-token']));

        $server->shouldReceive('respondToAccessTokenRequest')->with(
            Mockery::type('Psr\Http\Message\ServerRequestInterface'), Mockery::type('Psr\Http\Message\ResponseInterface')
        )->andReturn($psrResponse);

        $jwt = Mockery::mock(Lcobucci\JWT\Parser::class);
        // $jwt->shouldReceive('parse->getClaim')->andReturn('token-id');

        // $tokens->shouldReceive('find')->once()->with('token-id')->andReturn(new AccessTokenControllerTestStubToken);
        // $tokens->shouldReceive('revokeOtherAccessTokens')->once()->with(1, 2, 'token-id', false);

        $controller = new Laravel\Passport\Http\Controllers\AccessTokenController($server, $tokens, $jwt);

        $this->assertEquals('{"access_token":"access-token"}', $controller->issueToken(
            Mockery::mock('Psr\Http\Message\ServerRequestInterface')
        )->getContent());
    }

    public function test_exceptions_are_handled()
    {
        Container::getInstance()->instance(ExceptionHandler::class, $exceptions = Mockery::mock());
        $exceptions->shouldReceive('report')->once();

        $tokens = Mockery::mock(Laravel\Passport\TokenRepository::class);
        $jwt = Mockery::mock(Lcobucci\JWT\Parser::class);

        $server = Mockery::mock('League\OAuth2\Server\AuthorizationServer');
        $server->shouldReceive('respondToAccessTokenRequest')->with(
            Mockery::type('Psr\Http\Message\ServerRequestInterface'), Mockery::type('Psr\Http\Message\ResponseInterface')
        )->andThrow(new Exception('whoops'));

        $controller = new Laravel\Passport\Http\Controllers\AccessTokenController($server, $tokens, $jwt);

        $this->assertEquals('whoops', $controller->issueToken(Mockery::mock('Psr\Http\Message\ServerRequestInterface'))->getOriginalContent());
    }
}

class AccessTokenControllerTestStubToken
{
    public $client_id = 1;
    public $user_id = 2;
}
