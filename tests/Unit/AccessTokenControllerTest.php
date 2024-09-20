<?php

namespace Laravel\Passport\Tests\Unit;

use Laravel\Passport\Exceptions\OAuthServerException;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException as LeagueException;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AccessTokenControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_a_token_can_be_issued()
    {
        $request = m::mock(ServerRequestInterface::class);
        $request->shouldReceive('getParsedBody')->once()->andReturn([]);

        $response = m::type(ResponseInterface::class);

        $psrResponse = new Response();
        $psrResponse->getBody()->write(json_encode(['access_token' => 'access-token']));

        $server = m::mock(AuthorizationServer::class);
        $server->shouldReceive('respondToAccessTokenRequest')
            ->with($request, $response)
            ->andReturn($psrResponse);

        $controller = new AccessTokenController($server);

        $this->assertSame('{"access_token":"access-token"}', $controller->issueToken($request)->getContent());
    }

    public function test_exceptions_are_handled()
    {
        $request = m::mock(ServerRequestInterface::class);
        $request->shouldReceive('getParsedBody')->once()->andReturn([]);

        $server = m::mock(AuthorizationServer::class);
        $server->shouldReceive('respondToAccessTokenRequest')->with(
            $request, m::type(ResponseInterface::class)
        )->andThrow(LeagueException::invalidCredentials());

        $controller = new AccessTokenController($server);

        $this->expectException(OAuthServerException::class);

        $controller->issueToken($request);
    }
}

class AccessTokenControllerTestStubToken
{
    public $client_id = 1;

    public $user_id = 2;
}
