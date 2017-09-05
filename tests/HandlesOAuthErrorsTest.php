<?php

use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Response;

class HandlesOAuthErrorsTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
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
        Container::getInstance()->instance(ExceptionHandler::class, $handler = Mockery::mock());

        $controller = new HandlesOAuthErrorsStubController;
        $exception = new \League\OAuth2\Server\Exception\OAuthServerException('Error', 1, 'fatal');

        $handler->shouldReceive('report')->once()->with($exception);

        $result = $controller->test(function () use ($exception) {
            throw $exception;
        });

        $this->assertInstanceOf(Response::class, $result);
        $this->assertJsonStringEqualsJsonString('{"error":"fatal","message":"Error"}', $result->content());
    }

    public function testShouldHandleOtherExceptions()
    {
        Container::getInstance()->instance(ExceptionHandler::class, $handler = Mockery::mock());

        $controller = new HandlesOAuthErrorsStubController;
        $exception = new RuntimeException('Exception occurred', 1);

        $handler->shouldReceive('report')->once()->with($exception);

        $result = $controller->test(function () use ($exception) {
            throw $exception;
        });

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame('Exception occurred', $result->content());
    }

    public function testShouldHandleThrowables()
    {
        Container::getInstance()->instance(ExceptionHandler::class, $handler = Mockery::mock());

        $controller = new HandlesOAuthErrorsStubController;
        $exception = new Error('Fatal Error', 1);

        $handler->shouldReceive('report')
            ->once()
            ->with(Mockery::type(Exception::class));

        $result = $controller->test(function () use ($exception) {
            throw $exception;
        });

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame('Fatal Error', $result->content());
    }
}

class HandlesOAuthErrorsStubController
{
    use \Laravel\Passport\Http\Controllers\HandlesOAuthErrors;

    public function test($callback)
    {
        return $this->withErrorHandling($callback);
    }
}
