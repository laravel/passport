<?php

namespace Laravel\Passport\Jwt;

use Illuminate\Support\Collection;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory as Key;
use Lcobucci\JWT\Token\RegisteredClaims;

class Encoder
{
    public function encode(array $claims, string $passphrase): string
    {
        $builder = Configuration::forSymmetricSigner(new Sha256(), Key::plainText($passphrase))
            ->builder();

        collect($claims)
            ->tap(function (Collection $collection) use ($builder) {
                // Some claims have to be added via special methods
                // and will trigger deprecation calls to be added
                // via withClaims() method.
                $collection
                    ->only(RegisteredClaims::ALL)
                    ->each(function ($value, $key) use ($builder) {
                        if ($key === RegisteredClaims::AUDIENCE) {
                            // Must be converted to a string for certain versions of
                            // Lcobucci which filters out non-string values for the audience.
                            $builder->permittedFor((string) $value);

                            return;
                        }
                        if ($key === RegisteredClaims::SUBJECT) {
                            $builder->relatedTo($value);

                            return;
                        }
                    });
            })
            ->except(RegisteredClaims::ALL)
            ->each(function ($value, $key) use ($builder) {
                $builder->withClaim($key, $value);
            });

        return $builder
            ->getToken(new Sha256(), Key::plainText($passphrase))
            ->toString();
    }
}
