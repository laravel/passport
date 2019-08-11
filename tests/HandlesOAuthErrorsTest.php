<?php

namespace Laravel\Passport\Tests;

use Mockery as m;
use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;
use Laravel\Passport\Exceptions\OAuthServerException;
use Laravel\Passport\Http\Controllers\HandlesOAuthErrors;
use League\OAuth2\Server\Exception\OAuthServerException as LeagueException;

class HandlesOAuthErrorsTest extends TestCase
{
    public function tearDown()
    {
        m::close();
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
        $controller = new HandlesOAuthErrorsStubController;

        $exception = new LeagueException('Error', 1, 'fatal');

        $e = null;

        try {
            $controller->test(function () use ($exception) {
                throw $exception;
            });
        } catch (OAuthServerException $e) {
            $e = $e;
        }

        $this->assertInstanceOf(OAuthServerException::class, $e);
        $this->assertEquals('Error', $e->getMessage());
        $this->assertInstanceOf(LeagueException::class, $e->getPrevious());

        $response = $e->render(new Request);

        $this->assertJsonStringEqualsJsonString(
            '{"error":"fatal","error_description":"Error","message":"Error"}',
            $response->getContent()
        );
    }

    public function testShouldIgnoreOtherExceptions()
    {
        $controller = new HandlesOAuthErrorsStubController;

        $exception = new RuntimeException('Exception occurred', 1);

        $this->expectException(RuntimeException::class);

        $controller->test(function () use ($exception) {
            throw $exception;
        });
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
