<?php

namespace Laravel\Passport\Tests\Unit;

use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Matching\Argument;
use Laravel\Passport\Exceptions\OAuthServerException;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException as LeagueException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Response;

class AccessTokenControllerTest extends TestCase
{
    use VerifiesDoubles;

    public function test_a_token_can_be_issued()
    {
        $request = Double::for(ServerRequestInterface::class);

        $response = Argument::type(ResponseInterface::class);

        $psrResponse = (new PsrHttpFactory)->createResponse(new Response);
        $psrResponse->getBody()->write(json_encode(['access_token' => 'access-token']));

        $server = Double::for(AuthorizationServer::class);
        $server->allows('respondToAccessTokenRequest')->with($request, $response)->returns($psrResponse);

        $controller = new AccessTokenController($server);

        $this->assertSame('{"access_token":"access-token"}', $controller->issueToken($request, $psrResponse)->getContent());
    }

    public function test_exceptions_are_handled()
    {
        $request = Double::for(ServerRequestInterface::class);

        app()->instance(ResponseInterface::class, (new PsrHttpFactory)->createResponse(new Response));

        $server = Double::for(AuthorizationServer::class);
        $server->allows('respondToAccessTokenRequest')->with($request, Argument::type(ResponseInterface::class))->throws(LeagueException::invalidCredentials());

        $controller = new AccessTokenController($server);

        $this->expectException(OAuthServerException::class);

        $controller->issueToken($request, Double::for(ResponseInterface::class));
    }
}

class AccessTokenControllerTestStubToken
{
    public $client_id = 1;

    public $user_id = 2;
}
