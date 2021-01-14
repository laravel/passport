<?php

namespace Laravel\Passport\Tests\Feature\Console;

use Laravel\Passport\AuthCode;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Tests\Feature\PassportTestCase;
use Laravel\Passport\Token;

class PurgeCommand extends PassportTestCase
{
    public function test_purge()
    {
        $expired = now()->subDays(8);
        $notExpired = now();

        $accessTokenExpired = Token::create(['id' => 'a', 'user_id' => 1, 'client_id' => 1, 'revoked' => 0, 'expires_at' => $expired]);
        $accessTokenRevoked = Token::create(['id' => 'b', 'user_id' => 1, 'client_id' => 1, 'revoked' => 1, 'expires_at' => $notExpired]);
        $accessTokenOk = Token::create(['id' => 'c', 'user_id' => 1, 'client_id' => 1, 'revoked' => 0, 'expires_at' => $notExpired]);

        $authCodeExpired = AuthCode::create(['id' => 'a', 'user_id' => 1, 'client_id' => 1, 'revoked' => 0, 'expires_at' => $expired]);
        $authCodeRevoked = AuthCode::create(['id' => 'b', 'user_id' => 1, 'client_id' => 1, 'revoked' => 1, 'expires_at' => $notExpired]);
        $authCodeOk = AuthCode::create(['id' => 'c', 'user_id' => 1, 'client_id' => 1, 'revoked' => 0, 'expires_at' => $notExpired]);

        $refreshTokenExpired = RefreshToken::create(['id' => 'a', 'access_token_id' => $accessTokenExpired->id, 'revoked' => 0, 'expires_at' => $expired]);
        $refreshTokenRevoked = RefreshToken::create(['id' => 'b', 'access_token_id' => $accessTokenRevoked->id, 'revoked' => 1, 'expires_at' => $notExpired]);
        $refreshTokenInvalidAccessToken = RefreshToken::create(['id' => 'c', 'access_token_id' => 'xyz', 'revoked' => 0, 'expires_at' => $notExpired]);
        $refreshTokenOk = RefreshToken::create(['id' => 'd', 'access_token_id' => $accessTokenOk->id, 'revoked' => 0, 'expires_at' => $notExpired]);

        $this->artisan('passport:purge');

        $this->assertFalse(Token::whereKey($accessTokenExpired->id)->exists());
        $this->assertFalse(Token::whereKey($accessTokenRevoked->id)->exists());
        $this->assertTrue(Token::whereKey($accessTokenOk->id)->exists());

        $this->assertFalse(AuthCode::whereKey($authCodeExpired->id)->exists());
        $this->assertFalse(AuthCode::whereKey($authCodeRevoked->id)->exists());
        $this->assertTrue(AuthCode::whereKey($authCodeOk->id)->exists());

        $this->assertFalse(RefreshToken::whereKey($refreshTokenExpired->id)->exists());
        $this->assertFalse(RefreshToken::whereKey($refreshTokenRevoked->id)->exists());
        $this->assertFalse(RefreshToken::whereKey($refreshTokenInvalidAccessToken->id)->exists());
        $this->assertTrue(RefreshToken::whereKey($refreshTokenOk->id)->exists());
    }
}
