<?php

namespace Laravel\Passport;

use Firebase\JWT\JWT;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Date;
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

        $expiration = Date::now()->addMinutes((int) $config['lifetime'])->getTimestamp();

        return new Cookie(
            Passport::cookie(),
            $this->createToken($userId, $csrfToken, $expiration),
            $expiration,
            $config['path'],
            $config['domain'],
            $config['secure'],
            true,
            false,
            $config['same_site'] ?? null,
            $config['partitioned'] ?? false
        );
    }

    /**
     * Create a new JWT token for the given user ID and CSRF token.
     */
    protected function createToken(string|int $userId, string $csrfToken, int $expiration): string
    {
        return JWT::encode([
            'sub' => $userId,
            'csrf' => $csrfToken,
            'exp' => $expiration,
        ], Passport::tokenEncryptionKey($this->encrypter), 'HS256');
    }
}
