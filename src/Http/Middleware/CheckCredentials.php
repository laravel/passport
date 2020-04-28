<?php

namespace Laravel\Passport\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

abstract class CheckCredentials
{
    /**
     * The Resource Server instance.
     *
     * @var \League\OAuth2\Server\ResourceServer
     */
    protected $server;

    /**
     * Token Repository.
     *
     * @var \Laravel\Passport\TokenRepository
     */
    protected $repository;

    /**
     * Create a new middleware instance.
     *
     * @param  \League\OAuth2\Server\ResourceServer  $server
     * @param  \Laravel\Passport\TokenRepository  $repository
     * @return void
     */
    public function __construct(ResourceServer $server, TokenRepository $repository)
    {
        $this->server = $server;
        $this->repository = $repository;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$scopes
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$scopes)
    {
        $psr = (new PsrHttpFactory(
            new ServerRequestFactory,
            new StreamFactory,
            new UploadedFileFactory,
            new ResponseFactory
        ))->createRequest($request);

        try {
            $psr = $this->server->validateAuthenticatedRequest($psr);
        } catch (OAuthServerException $e) {
            throw new AuthenticationException;
        }

        $this->validate($psr, $scopes);

        return $next($request);
    }

    /**
     * Validate the scopes and token on the incoming request.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $psr
     * @param  array  $scopes
     * @return void
     *
     * @throws \Laravel\Passport\Exceptions\MissingScopeException|\Illuminate\Auth\AuthenticationException
     */
    protected function validate($psr, $scopes)
    {
        $token = $this->repository->find($psr->getAttribute('oauth_access_token_id'));

        $this->validateCredentials($token);

        $this->validateScopes($token, $scopes);
    }

    /**
     * Validate token credentials.
     *
     * @param  \Laravel\Passport\Token  $token
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    abstract protected function validateCredentials($token);

    /**
     * Validate token scopes.
     *
     * @param  \Laravel\Passport\Token  $token
     * @param  array  $scopes
     * @return void
     *
     * @throws \Laravel\Passport\Exceptions\MissingScopeException
     */
    abstract protected function validateScopes($token, $scopes);
}
