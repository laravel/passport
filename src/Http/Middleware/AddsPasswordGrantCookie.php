<?php

namespace Laravel\Passport\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Laravel\Passport\Passport;
use Illuminate\Encryption\Encrypter;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Config\Repository as Config;

class AddsPasswordGrantCookie
{
    /**
     * The configuration repository implementation.
     *
     * @var Config
     */
    protected $config;

    /**
     * The encrypter implementation.
     *
     * @var \Illuminate\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Config\Repository $config
     * @param  \Illuminate\Encryption\Encrypter $encrypter
     * @return void
     */
    public function __construct(Config $config, Encrypter $encrypter)
    {
        $this->config = $config;
        $this->encrypter = $encrypter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->setCookie($this->make($response));

        return $response;
    }

    /**
     * Create a new API token cookie.
     *
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    protected function make(Response $response)
    {
        $token = json_decode($response->getContent(), true);

        $config = $this->config->get('session');

        $expiration = Carbon::now()->addMinutes($token['expires_in']);

        return new Cookie(
            Passport::cookie(),
            $this->encrypter->encrypt($token['access_token']),
            $expiration,
            $config['path'],
            $config['domain'],
            $config['secure'],
            true
        );
    }
}
