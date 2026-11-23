<?php

namespace Laravel\Passport\Http\Controllers;

use Firebase\JWT\JWT;
use Illuminate\Http\JsonResponse;
use League\OAuth2\Server\CryptKey;
use phpseclib4\Crypt\PublicKeyLoader;
use phpseclib4\Crypt\RSA\PublicKey;
use Throwable;

class JsonWebKeySetController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ?CryptKey $publicKey,
    ) {
    }

    /**
     * Get the JSON Web Key Set containing the public key that signs access tokens.
     */
    public function __invoke(): JsonResponse
    {
        try {
            $key = $this->publicKey ? PublicKeyLoader::loadPublicKey($this->publicKey->getKeyContents()) : null;
        } catch (Throwable) {
            $key = null;
        }

        if (! $key instanceof PublicKey) {
            return new JsonResponse(['keys' => []]);
        }

        ['n' => $n, 'e' => $e] = json_decode($key->toString('JWK'), true)['keys'][0];

        return new JsonResponse(['keys' => [[
            'kty' => 'RSA',
            'n' => $n,
            'e' => $e,
            'alg' => 'RS256',
            'use' => 'sig',
            'kid' => JWT::urlsafeB64Encode(hash('sha256', json_encode(['e' => $e, 'kty' => 'RSA', 'n' => $n]), true)),
        ]]]);
    }
}
