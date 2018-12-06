<?php

namespace Laravel\Passport\Tests;

use Exception;
use Mockery as m;
use Lcobucci\JWT\Parser;
use Zend\Diactoros\Response;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Laravel\Passport\TokenRepository;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Laravel\Passport\Http\Controllers\AccessTokenController;

class AccessTokenControllerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_a_token_can_be_issued()
    {
        $server = m::mock('League\OAuth2\Server\AuthorizationServer');
        $tokens = m::mock(TokenRepository::class);

        $psrResponse = new Response();
        $psrResponse->getBody()->write(json_encode(['access_token' => 'access-token']));

        $server->shouldReceive('respondToAccessTokenRequest')->with(
            m::type('Psr\Http\Message\ServerRequestInterface'), m::type('Psr\Http\Message\ResponseInterface')
        )->andReturn($psrResponse);

        $jwt = m::mock(Parser::class);
        // $jwt->shouldReceive('parse->getClaim')->andReturn('token-id');

        // $tokens->shouldReceive('find')->once()->with('token-id')->andReturn(new AccessTokenControllerTestStubToken);
        // $tokens->shouldReceive('revokeOtherAccessTokens')->once()->with(1, 2, 'token-id', false);

        $controller = new AccessTokenController($server, $tokens, $jwt);

        $this->assertEquals('{"access_token":"access-token"}', $controller->issueToken(
            m::mock('Psr\Http\Message\ServerRequestInterface')
        )->getContent());
    }

    public function test_exceptions_are_handled()
    {
        Container::getInstance()->instance(ExceptionHandler::class, $exceptions = m::mock());
        Container::getInstance()->instance(Repository::class, $config = m::mock());
        $exceptions->shouldReceive('report')->once();
        $config->shouldReceive('get')->once()->andReturn(true);

        $tokens = m::mock(TokenRepository::class);
        $jwt = m::mock(Parser::class);

        $server = m::mock('League\OAuth2\Server\AuthorizationServer');
        $server->shouldReceive('respondToAccessTokenRequest')->with(
            m::type('Psr\Http\Message\ServerRequestInterface'), m::type('Psr\Http\Message\ResponseInterface')
        )->andThrow(new Exception('whoops'));

        $controller = new AccessTokenController($server, $tokens, $jwt);

        $this->assertEquals('whoops', $controller->issueToken(m::mock('Psr\Http\Message\ServerRequestInterface'))->getOriginalContent());
    }
}

class AccessTokenControllerTestStubToken
{
    public $client_id = 1;

    public $user_id = 2;
}
