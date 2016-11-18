<?php

namespace Laravel\Passport\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        if ($this->containsValidGrantType($request) && $response->isOk()) {
            $response->headers->setCookie($this->make($response));
        }

        return $response;
    }

    /**
     * Create a new API token cookie.
     *
     * @param  \Symfony\Component\HttpFoundation\Response $response
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    protected function make(Response $response)
    {
        $token = json_decode($response->getContent(), true);

        $config = $this->config->get('session');

        $expiration = Carbon::now()->addMinutes($token['expires_in']);

        $payload = [
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'],
        ];

        return new Cookie(
            Passport::cookie(),
            $this->encrypter->encrypt($payload),
            $expiration,
            $config['path'],
            $config['domain'],
            $config['secure'],
            true
        );
    }

    /**
     * Determine if the request contains a valid grant type.
     *
     * @param  \Illuminate\Http\Request $request
     * @return bool
     */
    protected function containsValidGrantType(Request $request)
    {
        return $request->grant_type === 'password' || $request->grant_type === 'refresh_token';
    }
}
