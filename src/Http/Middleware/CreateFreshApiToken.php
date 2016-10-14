<?php

namespace Laravel\Passport\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Response;
use Laravel\Passport\ApiTokenCookieFactory;

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
     * The authentication guard.
     *
     * @var string
     */
    protected $guard;

    /**
     * Create a new middleware instance.
     *
     * @param  ApiTokenCookieFactory  $cookieFactory
     * @param  Config  $config
     * @return void
     */
    public function __construct(ApiTokenCookieFactory $cookieFactory, Config $config)
    {
        $this->cookieFactory = $cookieFactory;
        $this->config = $config;
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
        $this->guard = $guard;

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
     * @param  \Illuminate\Http\Request $request
     * @param  \Illuminate\Http\Response $response
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
        return $request->isMethod('GET') && $request->user($this->guard);
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
        $cookie_name = $this->config->get('session.passport_cookie', 'laravel_token');
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $cookie_name) {
                return true;
            }
        }

        return false;
    }
}
