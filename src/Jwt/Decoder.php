<?php

namespace Laravel\Passport\Jwt;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory as Key;

class Decoder
{
    /**
     * @param  string $token
     * @param  string $passphrase
     * @return array
     */
    public function decode(string $token, string $passphrase): array
    {
        $decoded = Configuration::forSymmetricSigner(new Sha256(), Key::plainText($passphrase))
            ->parser()
            ->parse($token);

        return $decoded->claims()->all();
    }
}
