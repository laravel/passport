<?php

use Carbon\CarbonImmutable;
use Laravel\Passport\Bridge\AccessToken;
use Laravel\Passport\Bridge\AuthCode;
use Laravel\Passport\Bridge\RefreshToken;
use Laravel\Passport\Bridge\AccessTokenRepository as BridgeAccessTokenRepository;
use Laravel\Passport\Bridge\AuthCodeRepository as BridgeAuthCodeRepository;
use Laravel\Passport\Bridge\RefreshTokenRepository as BridgeRefreshTokenRepository;
use Laravel\Passport\RefreshTokenRepository;
use Laravel\Passport\Tests\Feature\PassportTestCase;
use Laravel\Passport\TokenRepository;
use Mockery as m;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;

class RevokedTest extends PassportTestCase
{
    use WithLaravelMigrations;

    public function test_it_can_determine_if_a_access_token_is_revoked()
    {
        $repository = $this->accessTokenRepository();
        $this->persistNewAccessToken($repository, 'tokenId');

        $repository->revokeAccessToken('tokenId');

        $this->assertTrue($repository->isAccessTokenRevoked('tokenId'));
    }

    public function test_a_access_token_is_also_revoked_if_it_cannot_be_found()
    {
        $repository = $this->accessTokenRepository();

        $this->assertTrue($repository->isAccessTokenRevoked('notExistingTokenId'));
    }

    public function test_it_can_determine_if_a_access_token_is_not_revoked()
    {
        $repository = $this->accessTokenRepository();
        $this->persistNewAccessToken($repository, 'tokenId');

        $this->assertFalse($repository->isAccessTokenRevoked('tokenId'));
    }

    public function test_it_can_determine_if_a_auth_code_is_revoked()
    {
        $repository = $this->authCodeRepository();
        $this->persistNewAuthCode($repository, 'tokenId');

        $repository->revokeAuthCode('tokenId');

        $this->assertTrue($repository->isAuthCodeRevoked('tokenId'));
    }

    public function test_a_auth_code_is_also_revoked_if_it_cannot_be_found()
    {
        $repository = $this->authCodeRepository();

        $this->assertTrue($repository->isAuthCodeRevoked('notExistingTokenId'));
    }

    public function test_it_can_determine_if_a_auth_code_is_not_revoked()
    {
        $repository = $this->authCodeRepository();
        $this->persistNewAuthCode($repository, 'tokenId');

        $this->assertFalse($repository->isAuthCodeRevoked('tokenId'));
    }

    public function test_it_can_determine_if_a_refresh_token_is_revoked()
    {
        $repository = $this->refreshTokenRepository();
        $this->persistNewRefreshToken($repository, 'tokenId');

        $repository->revokeRefreshToken('tokenId');

        $this->assertTrue($repository->isRefreshTokenRevoked('tokenId'));
    }

    public function test_a_refresh_token_is_also_revoked_if_it_cannot_be_found()
    {
        $repository = $this->refreshTokenRepository();

        $this->assertTrue($repository->isRefreshTokenRevoked('notExistingTokenId'));
    }

    public function test_it_can_determine_if_a_refresh_token_is_not_revoked()
    {
        $repository = $this->refreshTokenRepository();
        $this->persistNewRefreshToken($repository, 'tokenId');

        $this->assertFalse($repository->isRefreshTokenRevoked('tokenId'));
    }

    private function accessTokenRepository(): BridgeAccessTokenRepository
    {
        $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('dispatch');

        return new BridgeAccessTokenRepository(new TokenRepository, $events);
    }

    private function persistNewAccessToken(BridgeAccessTokenRepository $repository, string $id): void
    {
        $accessToken = m::mock(AccessToken::class);
        $accessToken->shouldReceive('getIdentifier')->andReturn($id);
        $accessToken->shouldReceive('getUserIdentifier')->andReturn('1');
        $accessToken->shouldReceive('getClient->getIdentifier')->andReturn('clientId');
        $accessToken->shouldReceive('getScopes')->andReturn([]);
        $accessToken->shouldReceive('getExpiryDateTime')->andReturn(CarbonImmutable::now());

        $repository->persistNewAccessToken($accessToken);
    }

    private function authCodeRepository(): BridgeAuthCodeRepository
    {
        return new BridgeAuthCodeRepository;
    }

    private function persistNewAuthCode(BridgeAuthCodeRepository $repository, string $id): void
    {
        $authCode = m::mock(AuthCode::class);
        $authCode->shouldReceive('getIdentifier')->andReturn($id);
        $authCode->shouldReceive('getUserIdentifier')->andReturn('1');
        $authCode->shouldReceive('getClient->getIdentifier')->andReturn('clientId');
        $authCode->shouldReceive('getExpiryDateTime')->andReturn(CarbonImmutable::now());
        $authCode->shouldReceive('getScopes')->andReturn([]);

        $repository->persistNewAuthCode($authCode);
    }

    private function refreshTokenRepository(): BridgeRefreshTokenRepository
    {
        $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('dispatch');

        return new BridgeRefreshTokenRepository(new RefreshTokenRepository, $events);
    }

    private function persistNewRefreshToken(BridgeRefreshTokenRepository $repository, string $id): void
    {
        $refreshToken = m::mock(RefreshToken::class);
        $refreshToken->shouldReceive('getIdentifier')->andReturn($id);
        $refreshToken->shouldReceive('getAccessToken->getIdentifier')->andReturn('accessTokenId');
        $refreshToken->shouldReceive('getExpiryDateTime')->andReturn(CarbonImmutable::now());

        $repository->persistNewRefreshToken($refreshToken);
    }
}
