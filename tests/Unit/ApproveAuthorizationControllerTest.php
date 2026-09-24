<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Matching\Argument;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Response;

class ApproveAuthorizationControllerTest extends TestCase
{
    use VerifiesDoubles;

    public function test_complete_authorization_request()
    {
        $server = Double::for(AuthorizationServer::class);

        $controller = new ApproveAuthorizationController($server);

        $request = Double::for(Request::class, override: true);
        $request->allows('session')->returns($session = Double::for(Session::class));
        $request->expects('isNotFilled')->with('auth_token')->returns(false);
        $request->expects('input')->with('auth_token')->returns('foo');

        $authRequest = new AuthorizationRequest;
        $authRequest->setGrantTypeId('authorization_code');

        $session->expects('pull')->with('authToken')->returns('foo');
        $session->expects('pull')->with('authRequest')->returns(serialize($authRequest));

        $psrResponse = (new PsrHttpFactory)->createResponse(new Response);
        $psrResponse->getBody()->write('response');

        $server->expects('completeAuthorizationRequest')->with(Argument::satisfies(fn (AuthorizationRequest $request) => $request->isAuthorizationApproved()),
            Argument::type(ResponseInterface::class))->returns($psrResponse);

        $this->assertSame('response', $controller->approve($request->instance(), $psrResponse)->getContent());
    }
}
