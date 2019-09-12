<?php

namespace Laravel\Passport\Tests;

use Error;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Response;
use Laravel\Passport\Http\Controllers\HandlesOAuthErrors;
use League\OAuth2\Server\Exception\OAuthServerException;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class HandlesOAuthErrorsTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        Container::getInstance()->flush();
    }

    public function testShouldReturnCallbackResultIfNoErrorIsThrown()
    {
        $controller = new HandlesOAuthErrorsStubController;
        $response = new Response;

        $result = $controller->test(function () use ($response) {
            return $response;
        });

        $this->assertSame($response, $result);
    }

    public function testShouldHandleOAuthServerException()
    {
        Container::getInstance()->instance(ExceptionHandler::class, $handler = m::mock());
        Container::getInstance()->instance(Repository::class, $config = m::mock());

        $controller = new HandlesOAuthErrorsStubController;
        $exception = new OAuthServerException('Error', 1, 'fatal');

        $handler->shouldReceive('report')->once()->with($exception);

        $result = $controller->test(function () use ($exception) {
            throw $exception;
        });

        $this->assertInstanceOf(Response::class, $result);
        $this->assertJsonStringEqualsJsonString('{"error":"fatal","error_description":"Error","message":"Error"}', $result->content());
    }

    public function testShouldHandleOtherExceptions()
    {
        Container::getInstance()->instance(ExceptionHandler::class, $handler = m::mock());
        Container::getInstance()->instance(Repository::class, $config = m::mock());

        $controller = new HandlesOAuthErrorsStubController;
        $exception = new RuntimeException('Exception occurred', 1);

        $handler->shouldReceive('report')->once()->with($exception);

        $config->shouldReceive('get')->once()->andReturn(true);

        $result = $controller->test(function () use ($exception) {
            throw $exception;
        });

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame('Exception occurred', $result->content());
    }

    public function testShouldHandleThrowables()
    {
        Container::getInstance()->instance(ExceptionHandler::class, $handler = m::mock());
        Container::getInstance()->instance(Repository::class, $config = m::mock());

        $controller = new HandlesOAuthErrorsStubController;
        $exception = new Error('Fatal Error', 1);

        $handler->shouldReceive('report')
            ->once()
            ->with(m::type(Exception::class));

        $config->shouldReceive('get')->once()->andReturn(true);

        $result = $controller->test(function () use ($exception) {
            throw $exception;
        });

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame('Fatal Error', $result->content());
    }
}

class HandlesOAuthErrorsStubController
{
    use HandlesOAuthErrors;

    public function test($callback)
    {
        return $this->withErrorHandling($callback);
    }
}
