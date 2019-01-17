<?php

namespace Laravel\Passport\Tests;

use Error;
use Exception;
use Mockery as m;
use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Laravel\Passport\Http\Middleware\HandleOAuthErrors;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class HandleOAuthErrorsTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_calls_without_exceptions_pass_correctly()
    {
        $middleware = $this->middleware();
        $response = new Response;

        $result = $middleware->handle(new Request, function () use ($response) {
            return $response;
        });

        $this->assertSame($response, $result);
    }

    public function test_it_handles_oauth_server_exceptions()
    {
        $exception = new OAuthServerException('Error', 1, 'fatal');
        $middleware = $this->middleware($exception);

        $result = $middleware->handle(new Request, function () use ($exception) {
            throw $exception;
        });

        $this->assertInstanceOf(Response::class, $result);
        $this->assertJsonStringEqualsJsonString('{"error":"fatal","message":"Error"}', $result->content());
    }

    public function test_it_handles_other_exceptions()
    {
        $exception = new RuntimeException('Exception occurred', 1);
        $middleware = $this->middleware($exception, $mockDebugReturn = true);

        $result = $middleware->handle(new Request, function () use ($exception) {
            throw $exception;
        });

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame('Exception occurred', $result->content());
    }

    public function test_it_handles_throwables()
    {
        $exception = new Error('Fatal Error', 1);
        $middleware = $this->middleware(m::type(FatalThrowableError::class), $mockDebugReturn = true);

        $result = $middleware->handle(new Request, function () use ($exception) {
            throw $exception;
        });

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame('Fatal Error', $result->content());
    }

    private function middleware($expectedException = null, bool $mockDebugReturn = false): HandleOAuthErrors
    {
        $config = m::mock(Repository::class);
        $exceptionHandler = m::mock(ExceptionHandler::class);

        if ($mockDebugReturn) {
            $config->shouldReceive('get')->with('app.debug')->once()->andReturn(true);
        }

        if ($expectedException) {
            $exceptionHandler->shouldReceive('report')->once()->with($expectedException);
        }

        return new HandleOAuthErrors($config, $exceptionHandler);
    }
}
