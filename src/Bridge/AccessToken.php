<?php

namespace Laravel\Passport\Bridge;

use DateTimeImmutable;
use Laravel\Passport\Passport;
use Lcobucci\JWT\Token;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;
use RuntimeException;

class AccessToken implements AccessTokenEntityInterface
{
    use AccessTokenTrait, EntityTrait, TokenEntityTrait;

    /**
     * Create a new token instance.
     *
     * @param  non-empty-string|null  $userIdentifier
     * @param  \League\OAuth2\Server\Entities\ScopeEntityInterface[]  $scopes
     */
    public function __construct(?string $userIdentifier, array $scopes, ClientEntityInterface $client)
    {
        if (! is_null($userIdentifier)) {
            $this->setUserIdentifier($userIdentifier);
        }

        foreach ($scopes as $scope) {
            $this->addScope($scope);
        }

        $this->setClient($client);
    }

    /**
     * {@inheritdoc}
     */
    private function convertToJWT(): Token
    {
        $this->initJwtConfiguration();

        return $this->jwtConfiguration->builder()
            ->withHeader('kid', $this->determineKid())
            ->permittedFor($this->getClient()->getIdentifier())
            ->identifiedBy($this->getIdentifier())
            ->issuedAt(new DateTimeImmutable())
            ->canOnlyBeUsedAfter(new DateTimeImmutable())
            ->expiresAt($this->getExpiryDateTime())
            ->relatedTo($this->getSubjectIdentifier())
            ->withClaim('scopes', $this->getScopes())
            ->getToken($this->jwtConfiguration->signer(), $this->jwtConfiguration->signingKey());
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return $this->convertToJWT()->toString();
    }

    /**
     * Determine the 'kid' (key ID) for the JWT header, based on the public key's JWK thumbprint.
     * See RFC 7638: https://tools.ietf.org/html/rfc7638.
     */
    protected function determineKid(): string
    {
        $pem = $this->getPublicKeyMaterial();

        $res = openssl_pkey_get_public($pem);
        if ($res === false) {
            throw new RuntimeException('Invalid public key material');
        }

        $details = openssl_pkey_get_details($res);
        if ($details === false || $details['type'] !== OPENSSL_KEYTYPE_RSA) {
            throw new RuntimeException('Only RSA public keys are supported');
        }

        // Base64url helpers
        $b64url = fn (string $bin) => rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');

        // Build minimal JWK (kty, n, e)
        $jwk = [
            'e' => $b64url($details['rsa']['e']),
            'kty' => 'RSA',
            'n' => $b64url($details['rsa']['n']),
        ];

        // Canonical JSON: keys sorted, no spaces or escapes that change semantics
        ksort($jwk);
        $json = json_encode($jwk, JSON_UNESCAPED_SLASHES);

        // RFC 7638 thumbprint = SHA-256 over canonical JSON, base64url-encoded
        $thumb = hash('sha256', $json, true);

        return $b64url($thumb);
    }

    /**
     * Find the public key material from config or file.
     */
    private function getPublicKeyMaterial(): string
    {
        if ($keyMaterial = config('passport.public_key')) {
            return $keyMaterial;
        }

        if (file_exists($publicKey = Passport::keyPath('oauth-public.key'))) {
            return file_get_contents($publicKey);
        }

        throw new RuntimeException('No public key available');
    }
}
