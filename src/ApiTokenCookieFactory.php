<?php

namespace Laravel\Passport;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Encryption\Encrypter;
use Symfony\Component\HttpFoundation\Cookie;

class ApiTokenCookieFactory
{
    /**
     * The configuration repository implementation.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * The encrypter implementation.
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * Create an API token cookie factory instance.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  \Illuminate\Contracts\Encryption\Encrypter  $encrypter
     * @return void
     */
    public function __construct(Config $config, Encrypter $encrypter)
    {
        $this->config = $config;
        $this->encrypter = $encrypter;
    }

    /**
     * Create a new API token cookie.
     *
     * @param  mixed  $userId
     * @param  string  $csrfToken
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    public function make($userId, $csrfToken, $guard = null)
    {
        $session = $this->config->get('session');
        $auth = $this->config->get('auth');

        $expiration = Carbon::now()->addMinutes($session['lifetime']);

        $provider = $auth['guards'][$guard ?: 'web']['provider'];

        return new Cookie(
            Passport::cookie(),
            $this->createToken($userId, $csrfToken, $expiration, $provider),
            $expiration,
            $session['path'],
            $session['domain'],
            $session['secure'],
            true,
            false,
            $session['same_site'] ?? null
        );
    }

    /**
     * Create a new JWT token for the given user ID and CSRF token.
     *
     * @param  mixed  $userId
     * @param  string  $csrfToken
     * @param  \Carbon\Carbon  $expiration
     * @return string
     */
    protected function createToken($userId, $csrfToken, Carbon $expiration, $provider)
    {
        return JWT::encode([
            'sub' => $userId,
            'provider' => $provider,
            'csrf' => $csrfToken,
            'expiry' => $expiration->getTimestamp(),
        ], $this->encrypter->getKey());
    }
}
