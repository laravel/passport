<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Http\Response;
use Laravel\Passport\Exceptions\OAuthServerException;
use Laravel\Passport\Http\Controllers\HandlesOAuthErrors;
use League\OAuth2\Server\Exception\OAuthServerException as LeagueException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class HandlesOAuthErrorsTest extends TestCase
{
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
        $this->assertSame('Error', $e->getMessage());
        $this->assertSame(1, $e->getCode());
        $this->assertInstanceOf(LeagueException::class, $e->getPrevious());

        $response = $e->getResponse();

        $this->assertJsonStringEqualsJsonString(
            '{"error":"fatal","error_description":"Error"}',
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
