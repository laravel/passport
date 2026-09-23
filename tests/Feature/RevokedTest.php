<?php

namespace Laravel\Passport\Tests\Feature;

use Carbon\CarbonImmutable;
use JMac\Testing\Double;
use Laravel\Passport\Bridge\AccessToken;
use Laravel\Passport\Bridge\AccessTokenRepository as BridgeAccessTokenRepository;
use Laravel\Passport\Bridge\AuthCode;
use Laravel\Passport\Bridge\AuthCodeRepository as BridgeAuthCodeRepository;
use Laravel\Passport\Bridge\DeviceCode;
use Laravel\Passport\Bridge\DeviceCodeRepository as BridgeDeviceCodeRepository;
use Laravel\Passport\Bridge\RefreshToken;
use Laravel\Passport\Bridge\RefreshTokenRepository as BridgeRefreshTokenRepository;
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

    public function test_it_can_determine_if_a_device_code_is_revoked()
    {
        $repository = $this->deviceCodeRepository();
        $this->persistNewDeviceCode($repository, 'deviceCodeId');

        $repository->revokeDeviceCode('deviceCodeId');

        $this->assertTrue($repository->isDeviceCodeRevoked('deviceCodeId'));
    }

    public function test_a_device_code_is_also_revoked_if_it_cannot_be_found()
    {
        $repository = $this->deviceCodeRepository();

        $this->assertTrue($repository->isDeviceCodeRevoked('notExistingDeviceCodeId'));
    }

    public function test_it_can_determine_if_a_device_code_is_not_revoked()
    {
        $repository = $this->deviceCodeRepository();
        $this->persistNewDeviceCode($repository, 'deviceCodeId');

        $this->assertFalse($repository->isDeviceCodeRevoked('deviceCodeId'));
    }

    private function accessTokenRepository(): BridgeAccessTokenRepository
    {
        $events = Double::for('Illuminate\Contracts\Events\Dispatcher');
        $events->allows('dispatch');

        return new BridgeAccessTokenRepository($events);
    }

    private function persistNewAccessToken(BridgeAccessTokenRepository $repository, string $id): void
    {
        $accessToken = Double::for(AccessToken::class);
        $accessToken->allows('getIdentifier')->returns($id);
        $accessToken->allows('getUserIdentifier')->returns('1');
        $accessToken->shouldReceive('getClient->getIdentifier')->andReturn('clientId');
        $accessToken->allows('getScopes')->returns([]);
        $accessToken->allows('getExpiryDateTime')->returns(CarbonImmutable::now());

        $repository->persistNewAccessToken($accessToken);
    }

    private function authCodeRepository(): BridgeAuthCodeRepository
    {
        return new BridgeAuthCodeRepository;
    }

    private function persistNewAuthCode(BridgeAuthCodeRepository $repository, string $id): void
    {
        $authCode = Double::for(AuthCode::class);
        $authCode->allows('getIdentifier')->returns($id);
        $authCode->allows('getUserIdentifier')->returns('1');
        $authCode->shouldReceive('getClient->getIdentifier')->andReturn('clientId');
        $authCode->allows('getExpiryDateTime')->returns(CarbonImmutable::now());
        $authCode->allows('getScopes')->returns([]);

        $repository->persistNewAuthCode($authCode);
    }

    private function refreshTokenRepository(): BridgeRefreshTokenRepository
    {
        $events = Double::for('Illuminate\Contracts\Events\Dispatcher');
        $events->allows('dispatch');

        return new BridgeRefreshTokenRepository($events);
    }

    private function persistNewRefreshToken(BridgeRefreshTokenRepository $repository, string $id): void
    {
        $refreshToken = Double::for(RefreshToken::class);
        $refreshToken->allows('getIdentifier')->returns($id);
        $refreshToken->shouldReceive('getAccessToken->getIdentifier')->andReturn('accessTokenId');
        $refreshToken->allows('getExpiryDateTime')->returns(CarbonImmutable::now());

        $repository->persistNewRefreshToken($refreshToken);
    }

    private function deviceCodeRepository(): BridgeDeviceCodeRepository
    {
        return new BridgeDeviceCodeRepository;
    }

    private function persistNewDeviceCode(BridgeDeviceCodeRepository $repository, string $id): void
    {
        $deviceCode = Double::for(DeviceCode::class);
        $deviceCode->allows('getIdentifier')->returns($id);
        $deviceCode->allows('getUserIdentifier')->returns(null);
        $deviceCode->shouldReceive('getClient->getIdentifier')->andReturn('clientId');
        $deviceCode->allows('getUserCode')->returns('userCode');
        $deviceCode->allows('getScopes')->returns([]);
        $deviceCode->allows('getExpiryDateTime')->returns(CarbonImmutable::now());
        $deviceCode->allows('getLastPolledAt')->returns(null);
        $deviceCode->allows('getUserApproved')->returns(false);

        $repository->persistDeviceCode($deviceCode);
    }
}
