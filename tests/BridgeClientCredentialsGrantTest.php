<?php

use Zend\Diactoros\ServerRequest;
use Laravel\Passport\Bridge\User;
use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\AccessToken;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

class BridgeClientCredentialsGrantTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function test_get_identifier()
    {
        $grant = new ClientCredentialsGrant();
        $this->assertEquals('client_credentials', $grant->getIdentifier());
    }

    public function test_respond_to_request()
    {
        $client = new Client(1, 'foo', 'http://www.example.com');
        $clientRepositoryMock = $this->getMockBuilder(ClientRepositoryInterface::class)->getMock();
        $clientRepositoryMock->method('getClientEntity')->willReturn($client);

        $accessToken = new AccessToken('bar');
        $accessTokenRepositoryMock = $this->getMockBuilder(AccessTokenRepositoryInterface::class)->getMock();
        $accessTokenRepositoryMock->method('getNewToken')->willReturn($accessToken);
        $accessTokenRepositoryMock->method('persistNewAccessToken')->willReturnSelf();

        $scopeRepositoryMock = $this->getMockBuilder(ScopeRepositoryInterface::class)->getMock();
        $scopeRepositoryMock->method('finalizeScopes')->willReturnArgument(0);

        $grant = new ClientCredentialsGrant();
        $grant->setClientRepository($clientRepositoryMock);
        $grant->setAccessTokenRepository($accessTokenRepositoryMock);
        $grant->setScopeRepository($scopeRepositoryMock);

        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withParsedBody(
            [
                'client_id'     => 'foo',
                'client_secret' => 'bar',
            ]
        );

        $responseType = new ResponseTypeStub();
        $grant->respondToAccessTokenRequest($serverRequest, $responseType, new \DateInterval('PT5M'));

        $this->assertTrue($responseType->getAccessToken() instanceof AccessTokenEntityInterface);

        $accessTokenClient = $responseType->getAccessToken()->getClient();
        $this->assertNull($accessTokenClient->getUser());
    }

    public function test_respond_to_request_if_user_is_attached()
    {
        $client = new Client(1, 'foo', 'http://www.example.com');
        $user = new User('baz');
        $client->setUser($user);
        $clientRepositoryMock = $this->getMockBuilder(ClientRepositoryInterface::class)->getMock();
        $clientRepositoryMock->method('getClientEntity')->willReturn($client);

        $accessToken = new AccessToken('bar');
        $accessTokenRepositoryMock = $this->getMockBuilder(AccessTokenRepositoryInterface::class)->getMock();
        $accessTokenRepositoryMock->method('getNewToken')->willReturn($accessToken);
        $accessTokenRepositoryMock->method('persistNewAccessToken')->willReturnSelf();

        $scopeRepositoryMock = $this->getMockBuilder(ScopeRepositoryInterface::class)->getMock();
        $scopeRepositoryMock->method('finalizeScopes')->willReturnArgument(0);

        $grant = new ClientCredentialsGrant();
        $grant->setClientRepository($clientRepositoryMock);
        $grant->setAccessTokenRepository($accessTokenRepositoryMock);
        $grant->setScopeRepository($scopeRepositoryMock);

        $serverRequest = new ServerRequest();
        $serverRequest = $serverRequest->withParsedBody(
            [
                'client_id'     => 'foo',
                'client_secret' => 'bar',
            ]
        );

        $responseType = new ResponseTypeStub();
        $grant->respondToAccessTokenRequest($serverRequest, $responseType, new \DateInterval('PT5M'));

        $this->assertTrue($responseType->getAccessToken() instanceof AccessTokenEntityInterface);

        $accessTokenClient = $responseType->getAccessToken()->getClient();
        $this->assertInstanceOf(User::class, $accessTokenClient->getUser());
    }
}

class ResponseTypeStub implements ResponseTypeInterface
{
    protected $accessToken;

    public function setAccessToken(AccessTokenEntityInterface $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function setRefreshToken(\League\OAuth2\Server\Entities\RefreshTokenEntityInterface $refreshToken)
    {
        // No-op
    }

    public function generateHttpResponse(\Psr\Http\Message\ResponseInterface $response)
    {
        // No-op
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }
}
