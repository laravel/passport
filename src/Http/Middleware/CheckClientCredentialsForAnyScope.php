<?php

namespace Laravel\Passport\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Laravel\Passport\Exceptions\MissingScopeException;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Zend\Diactoros\ResponseFactory;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\StreamFactory;
use Zend\Diactoros\UploadedFileFactory;

class CheckClientCredentialsForAnyScope
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
     * @throws \Illuminate\Auth\AuthenticationException|\Laravel\Passport\Exceptions\MissingScopeException
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

        if ($this->validate($psr, $scopes)) {
            return $next($request);
        }

        throw new MissingScopeException($scopes);
    }

    /**
     * Validate the scopes and token on the incoming request.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $psr
     * @param  array  $scopes
     * @return bool
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function validate($psr, $scopes)
    {
        $token = $this->repository->find($psr->getAttribute('oauth_access_token_id'));

        if (! $token || $token->client->firstParty()) {
            throw new AuthenticationException;
        }

        if (in_array('*', $token->scopes)) {
            return true;
        }

        foreach ($scopes as $scope) {
            if ($token->can($scope)) {
                return true;
            }
        }

        return false;
    }
}
