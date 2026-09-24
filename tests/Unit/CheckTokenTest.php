<?php

namespace Laravel\Passport\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\Exceptions\AuthenticationException;
use Laravel\Passport\Http\Middleware\CheckToken;
use Laravel\Passport\TransientToken;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class CheckTokenTest extends TestCase
{
    use VerifiesDoubles;

    public function test_request_is_passed_along_if_token_is_valid()
    {
        $resourceServer = Double::for(ResourceServer::class);
        $resourceServer->expects('validateAuthenticatedRequest')->returns($psr = Double::for(ServerRequestInterface::class));
        $psr->expects('getAttributes')->returns([
            'oauth_user_id' => 1,
            'oauth_client_id' => 1,
            'oauth_access_token_id' => 'token',
            'oauth_scopes' => ['*'],
        ]);

        $middleware = new CheckToken($resourceServer);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $response = $middleware->handle($request, function () {
            return new Response('response');
        });

        $this->assertSame('response', $response->getContent());
    }

    public function test_request_is_passed_along_if_token_is_transient()
    {
        $user = Double::for(OAuthenticatable::class);
        $user->allows('currentAccessToken')->returns(new TransientToken());

        $resourceServer = Double::for(ResourceServer::class);
        $resourceServer->expects('validateAuthenticatedRequest')->never();

        $middleware = new CheckToken($resourceServer);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, function () {
            return new Response('response');
        });

        $this->assertSame('response', $response->getContent());
    }

    public function test_request_is_passed_along_if_token_and_scope_are_valid()
    {
        $resourceServer = Double::for(ResourceServer::class);
        $resourceServer->expects('validateAuthenticatedRequest')->returns($psr = Double::for(ServerRequestInterface::class));
        $psr->expects('getAttributes')->returns([
            'oauth_user_id' => 1,
            'oauth_client_id' => 1,
            'oauth_access_token_id' => 'token',
            'oauth_scopes' => ['see-profile'],
        ]);

        $middleware = new CheckToken($resourceServer);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $response = $middleware->handle($request, function () {
            return new Response('response');
        }, 'see-profile');

        $this->assertSame('response', $response->getContent());
    }

    public function test_exception_is_thrown_when_oauth_throws_exception()
    {
        $this->expectException(AuthenticationException::class);

        $resourceServer = Double::for(ResourceServer::class);
        $resourceServer->expects('validateAuthenticatedRequest')->throws(new OAuthServerException('message', 500, 'error type'));

        $middleware = new CheckToken($resourceServer);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $middleware->handle($request, function () {
            return 'response';
        });
    }

    public function test_exception_is_thrown_if_token_does_not_have_required_scopes()
    {
        $this->expectException('Laravel\Passport\Exceptions\MissingScopeException');

        $resourceServer = Double::for(ResourceServer::class);
        $resourceServer->expects('validateAuthenticatedRequest')->returns($psr = Double::for(ServerRequestInterface::class));
        $psr->expects('getAttributes')->returns([
            'oauth_user_id' => 1,
            'oauth_client_id' => 1,
            'oauth_access_token_id' => 'token',
            'oauth_scopes' => ['foo', 'notbar'],
        ]);

        $middleware = new CheckToken($resourceServer);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $response = $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }

    public function test_request_is_passed_along_if_scopes_are_present_on_token()
    {
        $resourceServer = Double::for(ResourceServer::class);
        $middleware = new CheckToken($resourceServer);
        $request = Double::for(Request::class, override: true);
        $request->allows('user')->returns($user = Double::for(OAuthenticatable::class));
        $user->allows('currentAccessToken')->returns($token = Double::for(AccessToken::class));
        $token->expects('cant')->with('foo')->returns(false);
        $token->expects('cant')->with('bar')->returns(false);

        $response = $middleware->handle($request->instance(), function () {
            return new Response('response');
        }, 'foo', 'bar');

        $this->assertSame('response', $response->getContent());
    }

    public function test_exception_is_thrown_if_token_doesnt_have_scope()
    {
        $this->expectException('Laravel\Passport\Exceptions\MissingScopeException');

        $resourceServer = Double::for(ResourceServer::class);
        $middleware = new CheckToken($resourceServer);
        $request = Double::for(Request::class, override: true);
        $request->allows('user')->returns($user = Double::for(OAuthenticatable::class));
        $user->allows('currentAccessToken')->returns($token = Double::for(AccessToken::class));
        $token->expects('cant')->with('foo')->returns(true);

        $middleware->handle($request->instance(), function () {
            return new Response('response');
        }, 'foo', 'bar');
    }
}
