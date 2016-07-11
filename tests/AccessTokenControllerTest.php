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
        $server->shouldReceive('respondToAccessTokenRequest')->with(
            Mockery::type('Psr\Http\Message\ServerRequestInterface'), Mockery::type('Psr\Http\Message\ResponseInterface')
        )->andReturn('response');

        $controller = new Laravel\Passport\Http\Controllers\AccessTokenController($server);

        $this->assertEquals('response', $controller->issueToken(Mockery::mock('Psr\Http\Message\ServerRequestInterface')));
    }

    public function test_exceptions_are_handled()
    {
        $container = new Container;
        Container::setInstance($container);
        $container->instance(ExceptionHandler::class, $exceptions = Mockery::mock());
        $exceptions->shouldReceive('report')->once();

        $server = Mockery::mock('League\OAuth2\Server\AuthorizationServer');
        $server->shouldReceive('respondToAccessTokenRequest')->with(
            Mockery::type('Psr\Http\Message\ServerRequestInterface'), Mockery::type('Psr\Http\Message\ResponseInterface')
        )->andReturnUsing(function () { throw new Exception('whoops'); });

        $controller = new Laravel\Passport\Http\Controllers\AccessTokenController($server);

        $this->assertEquals('whoops', $controller->issueToken(Mockery::mock('Psr\Http\Message\ServerRequestInterface'))->getOriginalContent());
    }
}
