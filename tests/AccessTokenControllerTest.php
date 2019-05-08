<?php

namespace Laravel\Passport\Tests;

use Exception;
use Mockery as m;
use Lcobucci\JWT\Parser;
use Zend\Diactoros\Response;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Laravel\Passport\TokenRepository;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Contracts\Config\Repository;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\AuthorizationServer;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Laravel\Passport\Http\Controllers\AccessTokenController;

class AccessTokenControllerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
        Container::getInstance()->flush();
    }

    public function test_a_token_can_be_issued()
    {
        $request = m::mock(ServerRequestInterface::class);
        $response = m::type(ResponseInterface::class);
        $tokens = m::mock(TokenRepository::class);
        $jwt = m::mock(Parser::class);

        $psrResponse = new Response();
        $psrResponse->getBody()->write(json_encode(['access_token' => 'access-token']));

        $server = m::mock(AuthorizationServer::class);
        $server->shouldReceive('respondToAccessTokenRequest')
            ->with($request, $response)
            ->andReturn($psrResponse);

        $controller = new AccessTokenController($server, $tokens, $jwt);

        $this->assertEquals('{"access_token":"access-token"}', $controller->issueToken($request)->getContent());
    }

    public function test_exceptions_are_handled()
    {
        Container::getInstance()->instance(ExceptionHandler::class, $exceptions = m::mock());
        Container::getInstance()->instance(Repository::class, $config = m::mock());
        $exceptions->shouldReceive('report')->once();
        $config->shouldReceive('get')->once()->andReturn(true);

        $request = m::mock(ServerRequestInterface::class);
        $response = m::type(ResponseInterface::class);
        $tokens = m::mock(TokenRepository::class);
        $jwt = m::mock(Parser::class);

        $server = m::mock(AuthorizationServer::class);
        $server->shouldReceive('respondToAccessTokenRequest')
            ->with($request, $response)
            ->andThrow(new Exception('whoops'));

        $controller = new AccessTokenController($server, $tokens, $jwt);

        $this->assertEquals('whoops', $controller->issueToken($request)->getOriginalContent());
    }
}

class AccessTokenControllerTestStubToken
{
    public $client_id = 1;

    public $user_id = 2;
}
