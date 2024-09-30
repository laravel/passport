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
     * Create an API token cookie factory instance.
     */
    public function __construct(
        protected Config $config,
        protected Encrypter $encrypter
    ) {
    }

    /**
     * Create a new API token cookie.
     */
    public function make(string|int $userId, string $csrfToken): Cookie
    {
        $config = $this->config->get('session');

        $expiration = Carbon::now()->addMinutes((int) $config['lifetime']);

        return new Cookie(
            Passport::cookie(),
            $this->createToken($userId, $csrfToken, $expiration),
            $expiration,
            $config['path'],
            $config['domain'],
            $config['secure'],
            true,
            false,
            $config['same_site'] ?? null
        );
    }

    /**
     * Create a new JWT token for the given user ID and CSRF token.
     */
    protected function createToken(string|int $userId, string $csrfToken, Carbon $expiration): string
    {
        return JWT::encode([
            'sub' => $userId,
            'csrf' => $csrfToken,
            'expiry' => $expiration->getTimestamp(),
        ], Passport::tokenEncryptionKey($this->encrypter), 'HS256');
    }
}
