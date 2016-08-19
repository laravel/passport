<?php

namespace Laravel\Passport\Http\Middleware;

use Closure;
use Illuminate\Http\Response;
use Laravel\Passport\ApiTokenCookieFactory;
use Illuminate\Contracts\Config\Repository as Config;

class CreateFreshApiToken
{
    /**
     * The configuration repository implementation.
     *
     * @var Config
     */
    protected $config;

    /**
     * The API token cookie factory instance.
     *
     * @var ApiTokenCookieFactory
     */
    protected $cookieFactory;

    /**
     * Create a new middleware instance.
     *
     * @param  Config  $config
     * @param  ApiTokenCookieFactory  $cookieFactory
     * @return void
     */
    public function __construct(Config $config, ApiTokenCookieFactory $cookieFactory)
    {
        $this->config = $config;
        $this->cookieFactory = $cookieFactory;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $response = $next($request);

        if ($this->shouldReceiveFreshToken($request, $response)) {
            $response->withCookie($this->cookieFactory->make(
                $request->user()->getKey(), $request->session()->token()
            ));
        }

        return $response;
    }

    /**
     * Determine if the given request should receive a fresh token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldReceiveFreshToken($request, $response)
    {
        return $this->requestShouldReceiveFreshToken($request) &&
               $this->responseShouldReceiveFreshToken($response);
    }

    /**
     * Determine if the request should receive a fresh token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function requestShouldReceiveFreshToken($request)
    {
        return $request->isMethod('GET') && $request->user();
    }

    /**
     * Determine if the response should receive a fresh token.
     *
     * @param  \Illuminate\Http\Response  $request
     * @return bool
     */
    protected function responseShouldReceiveFreshToken($response)
    {
        return $response instanceof Response &&
                    ! $this->alreadyContainsToken($response);
    }

    /**
     * Determine if the given response already contains an API token.
     *
     * This avoids us overwriting a just "refreshed" token.
     *
     * @param  \Illuminate\Http\Response  $response
     * @return bool
     */
    protected function alreadyContainsToken($response)
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $this->config->get('session.token_cookie', 'laravel_token')) {
                return true;
            }
        }

        return false;
    }
}
