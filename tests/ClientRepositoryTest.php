<?php

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use PHPUnit\Framework\TestCase;

class ClientRepositoryTest extends TestCase
{
    public function test_can_retrieve_client_from_token()
    {
        $repo = new \Laravel\Passport\ClientRepository();

        $bearerToken = (string) $this->generateToken();

        $client = $repo->findbyToken($bearerToken);

        $this->assertNotNull($client);
    }

    public static function generateToken()
    {
        return (new Builder())->setIssuer('http://example.com') // Configures the issuer (iss claim)
            ->setAudience('12345678') // Configures the audience (aud claim)
            ->setId('4f1g23a12aa', true) // Configures the id (jti claim), replicating as a header item
            ->setIssuedAt(time())
            ->setHeader('typ', 'JWT')
            ->setHeader('alg', 'HS256')
            ->setExpiration(time() + 604800)
            ->sign(new Sha256(), 'test.jwt.signature')
            ->getToken();
    }

}