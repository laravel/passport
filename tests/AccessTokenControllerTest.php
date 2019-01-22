<?php

namespace Laravel\Passport\Tests;

use Mockery as m;
use Lcobucci\JWT\Parser;
use Zend\Diactoros\Response;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\TokenRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\AuthorizationServer;
use Laravel\Passport\Http\Controllers\AccessTokenController;

class AccessTokenControllerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
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
}

class AccessTokenControllerTestStubToken
{
    public $client_id = 1;

    public $user_id = 2;
}
