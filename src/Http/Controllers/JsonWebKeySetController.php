<?php

namespace Laravel\Passport\Http\Controllers;

use Firebase\JWT\JWT;
use Illuminate\Http\JsonResponse;
use League\OAuth2\Server\CryptKey;

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
        $key = $this->publicKey ? openssl_pkey_get_public($this->publicKey->getKeyContents()) : false;

        $rsa = $key ? openssl_pkey_get_details($key)['rsa'] ?? null : null;

        if (! $rsa) {
            return new JsonResponse(['keys' => []]);
        }

        $n = JWT::urlsafeB64Encode($rsa['n']);
        $e = JWT::urlsafeB64Encode($rsa['e']);

        return new JsonResponse(['keys' => [[
            'kty' => 'RSA',
            'n' => $n,
            'e' => $e,
            'alg' => 'RS256',
            'use' => 'sig',
            'kid' => JWT::urlsafeB64Encode(hash('sha256', '{"e":"'.$e.'","kty":"RSA","n":"'.$n.'"}', true)),
        ]]]);
    }
}
