<?php

namespace Laravel\Passport;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Laravel\Passport\Contracts\AuthorizationViewResponse;
use Laravel\Passport\Contracts\DeviceAuthorizationViewResponse;
use Laravel\Passport\Contracts\DeviceUserCodeViewResponse;
use Laravel\Passport\Http\Responses\SimpleViewResponse;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Mockery;
use Psr\Http\Message\ServerRequestInterface;

class Passport
{
    /**
     * Indicates if Passport should validate the permissions of its encryption keys.
     */
    public static bool $validateKeyPermissions = true;

    /**
     * Indicates if the refresh token should be revoked after use.
     */
    public static bool $revokeRefreshTokenAfterUse = true;

    /**
     * Indicates if the implicit grant type is enabled.
     */
    public static bool $implicitGrantEnabled = false;

    /**
     * Indicates if the password grant type is enabled.
     */
    public static bool $passwordGrantEnabled = false;

    /**
     * The default scope.
     */
    public static string $defaultScope = '';

    /**
     * All of the scopes defined for the application.
     *
     * @var array<string, string>
     */
    public static array $scopes = [
        //
    ];

    /**
     * The interval when access tokens expire.
     */
    public static ?DateInterval $tokensExpireIn = null;

    /**
     * The date when refresh tokens expire.
     */
    public static ?DateInterval $refreshTokensExpireIn = null;

    /**
     * The date when personal access tokens expire.
     */
    public static ?DateInterval $personalAccessTokensExpireIn = null;

    /**
     * The name for API token cookies.
     */
    public static string $cookie = 'laravel_token';

    /**
     * Indicates if Passport should ignore incoming CSRF tokens.
     */
    public static bool $ignoreCsrfToken = false;

    /**
     * The storage location of the encryption keys.
     */
    public static ?string $keyPath = null;

    /**
     * The access token entity class name.
     *
     * @var class-string<\Laravel\Passport\Bridge\AccessToken>
     */
    public static string $accessTokenEntity = Bridge\AccessToken::class;

    /**
     * The auth code model class name.
     *
     * @var class-string<\Laravel\Passport\AuthCode>
     */
    public static string $authCodeModel = AuthCode::class;

    /**
     * The device code model class name.
     *
     * @var class-string<\Laravel\Passport\DeviceCode>
     */
    public static string $deviceCodeModel = DeviceCode::class;

    /**
     * The client model class name.
     *
     * @var class-string<\Laravel\Passport\Client>
     */
    public static string $clientModel = Client::class;

    /**
     * Indicates if clients are identified by UUIDs.
     */
    public static bool $clientUuids = true;

    /**
     * The token model class name.
     *
     * @var class-string<\Laravel\Passport\Token>
     */
    public static string $tokenModel = Token::class;

    /**
     * The refresh token model class name.
     *
     * @var class-string<\Laravel\Passport\RefreshToken>
     */
    public static string $refreshTokenModel = RefreshToken::class;

    /**
     * Indicates if Passport should unserializes cookies.
     */
    public static bool $unserializesCookies = false;

    /**
     * Indicates if Passport should decrypt cookies.
     */
    public static bool $decryptsCookies = true;

    /**
     * The callback that should be used to generate JWT encryption keys.
     *
     * @var (\Closure(\Illuminate\Contracts\Encryption\Encrypter): string)|null
     */
    public static ?Closure $tokenEncryptionKeyCallback = null;

    /**
     * Indicates the scope should inherit its parent scope.
     */
    public static bool $withInheritedScopes = false;

    /**
     * The authorization server response type.
     */
    public static ?ResponseTypeInterface $authorizationServerResponseType = null;

    /**
     * Indicates if Passport routes will be registered.
     */
    public static bool $registersRoutes = true;

    /**
     * Indicates if Passport JSON API routes will be registered.
     *
     * @var bool
     */
    public static $registersJsonApiRoutes = false;

    /**
     * Enable the implicit grant type.
     */
    public static function enableImplicitGrant(): void
    {
        static::$implicitGrantEnabled = true;
    }

    /**
     * Enable the password grant type.
     */
    public static function enablePasswordGrant(): void
    {
        static::$passwordGrantEnabled = true;
    }

    /**
     * Set the default scope(s). Multiple scopes may be an array or specified delimited by spaces.
     *
     * @deprecated Use defaultScopes.
     *
     * @param  string[]|string  $scope
     */
    public static function setDefaultScope(array|string $scope): void
    {
        static::$defaultScope = is_array($scope) ? implode(' ', $scope) : $scope;
    }

    /**
     * Set or get the default scopes.
     *
     * @param  string[]|string|null  $scopes
     * @return string[]
     */
    public static function defaultScopes(array|string|null $scopes = null): array
    {
        if (! is_null($scopes)) {
            static::$defaultScope = is_array($scopes) ? implode(' ', $scopes) : $scopes;
        }

        return static::$defaultScope ? explode(' ', static::$defaultScope) : [];
    }

    /**
     * Return the scopes in the given list that are actually defined scopes for the application.
     *
     * @param  string[]  $scopes
     * @return string[]
     */
    public static function validScopes(array $scopes): array
    {
        return array_values(array_unique(array_intersect($scopes, array_keys(static::$scopes))));
    }

    /**
     * Get all of the defined scope IDs.
     *
     * @return string[]
     */
    public static function scopeIds(): array
    {
        return static::scopes()->pluck('id')->values()->all();
    }

    /**
     * Determine if the given scope has been defined.
     */
    public static function hasScope(string $id): bool
    {
        return $id === '*' || array_key_exists($id, static::$scopes);
    }

    /**
     * Get all of the scopes defined for the application.
     *
     * @return \Illuminate\Support\Collection<int, \Laravel\Passport\Scope>
     */
    public static function scopes(): Collection
    {
        return collect(static::$scopes)->map(
            fn (string $description, string $id): Scope => new Scope($id, $description)
        )->values();
    }

    /**
     * Get all of the scopes matching the given IDs.
     *
     * @param  string[]  $ids
     * @return \Laravel\Passport\Scope[]
     */
    public static function scopesFor(array $ids): array
    {
        return collect($ids)->map(
            fn (string $id): ?Scope => isset(static::$scopes[$id]) ? new Scope($id, static::$scopes[$id]) : null
        )->filter()->values()->all();
    }

    /**
     * Define the scopes for the application.
     *
     * @param  array<string, string>  $scopes
     */
    public static function tokensCan(array $scopes): void
    {
        static::$scopes = $scopes;
    }

    /**
     * Get or set when access tokens expire.
     */
    public static function tokensExpireIn(DateTimeInterface|DateInterval|null $date = null): DateInterval
    {
        if (is_null($date)) {
            return static::$tokensExpireIn ??= new DateInterval('P1Y');
        }

        return static::$tokensExpireIn = $date instanceof DateTimeInterface
            ? Date::now()->diff($date)
            : $date;
    }

    /**
     * Get or set when refresh tokens expire.
     */
    public static function refreshTokensExpireIn(DateTimeInterface|DateInterval|null $date = null): DateInterval
    {
        if (is_null($date)) {
            return static::$refreshTokensExpireIn ??= new DateInterval('P1Y');
        }

        return static::$refreshTokensExpireIn = $date instanceof DateTimeInterface
            ? Date::now()->diff($date)
            : $date;
    }

    /**
     * Get or set when personal access tokens expire.
     */
    public static function personalAccessTokensExpireIn(DateTimeInterface|DateInterval|null $date = null): DateInterval
    {
        if (is_null($date)) {
            return static::$personalAccessTokensExpireIn ??= new DateInterval('P1Y');
        }

        return static::$personalAccessTokensExpireIn = $date instanceof DateTimeInterface
            ? Date::now()->diff($date)
            : $date;
    }

    /**
     * Get or set the name for API token cookies.
     */
    public static function cookie(?string $cookie = null): string
    {
        if (is_null($cookie)) {
            return static::$cookie;
        }

        return static::$cookie = $cookie;
    }

    /**
     * Indicate that Passport should ignore incoming CSRF tokens.
     */
    public static function ignoreCsrfToken(bool $ignoreCsrfToken = true): void
    {
        static::$ignoreCsrfToken = $ignoreCsrfToken;
    }

    /**
     * Set the current user for the application with the given scopes.
     *
     * @param  \Laravel\Passport\Contracts\OAuthenticatable  $user
     * @param  string[]  $scopes
     * @return \Laravel\Passport\Contracts\OAuthenticatable
     */
    public static function actingAs(Authenticatable $user, array $scopes = [], ?string $guard = 'api'): Authenticatable
    {
        $token = new AccessToken([
            'oauth_user_id' => $user->getAuthIdentifier(),
            'oauth_scopes' => $scopes,
        ]);

        $user->withAccessToken($token);

        if (isset($user->wasRecentlyCreated) && $user->wasRecentlyCreated) {
            $user->wasRecentlyCreated = false;
        }

        app('auth')->guard($guard)->setUser($user);

        app('auth')->shouldUse($guard);

        return $user;
    }

    /**
     * Set the current client for the application with the given scopes.
     *
     * @param  string[]  $scopes
     */
    public static function actingAsClient(Client $client, array $scopes = [], ?string $guard = 'api'): Client
    {
        $mock = Mockery::mock(ResourceServer::class);
        $mock->shouldReceive('validateAuthenticatedRequest')->andReturnUsing(
            fn (ServerRequestInterface $request) => $request
                ->withAttribute('oauth_client_id', $client->getKey())
                ->withAttribute('oauth_scopes', $scopes)
        );

        app()->instance(ResourceServer::class, $mock);

        app('auth')->guard($guard)->setClient($client);

        app('auth')->shouldUse($guard);

        return $client;
    }

    /**
     * Set the storage location of the encryption keys.
     */
    public static function loadKeysFrom(string $path): void
    {
        static::$keyPath = $path;
    }

    /**
     * The location of the encryption keys.
     */
    public static function keyPath(string $file): string
    {
        $file = ltrim($file, '/\\');

        return isset(static::$keyPath)
            ? rtrim(static::$keyPath, '/\\').DIRECTORY_SEPARATOR.$file
            : storage_path($file);
    }

    /**
     * Set the access token entity class name.
     *
     * @param  class-string<\Laravel\Passport\Bridge\AccessToken>  $accessTokenEntity
     */
    public static function useAccessTokenEntity(string $accessTokenEntity): void
    {
        static::$accessTokenEntity = $accessTokenEntity;
    }

    /**
     * Set the auth code model class name.
     *
     * @param  class-string<\Laravel\Passport\AuthCode>  $authCodeModel
     */
    public static function useAuthCodeModel(string $authCodeModel): void
    {
        static::$authCodeModel = $authCodeModel;
    }

    /**
     * Get the auth code model class name.
     *
     * @return class-string<\Laravel\Passport\AuthCode>
     */
    public static function authCodeModel(): string
    {
        return static::$authCodeModel;
    }

    /**
     * Get a new auth code model instance.
     */
    public static function authCode(): AuthCode
    {
        return new static::$authCodeModel;
    }

    /**
     * Set the device code model class name.
     *
     * @param  class-string<\Laravel\Passport\DeviceCode>  $deviceCodeModel
     */
    public static function useDeviceCodeModel(string $deviceCodeModel): void
    {
        static::$deviceCodeModel = $deviceCodeModel;
    }

    /**
     * Get the device code model class name.
     *
     * @return class-string<\Laravel\Passport\DeviceCode>
     */
    public static function deviceCodeModel(): string
    {
        return static::$deviceCodeModel;
    }

    /**
     * Get a new device code model instance.
     */
    public static function deviceCode(): DeviceCode
    {
        return new static::$deviceCodeModel;
    }

    /**
     * Set the client model class name.
     *
     * @param  class-string<\Laravel\Passport\Client>  $clientModel
     */
    public static function useClientModel(string $clientModel): void
    {
        static::$clientModel = $clientModel;
    }

    /**
     * Get the client model class name.
     *
     * @return class-string<\Laravel\Passport\Client>
     */
    public static function clientModel(): string
    {
        return static::$clientModel;
    }

    /**
     * Get a new client model instance.
     */
    public static function client(): Client
    {
        return new static::$clientModel;
    }

    /**
     * Set the token model class name.
     *
     * @param  class-string<\Laravel\Passport\Token>  $tokenModel
     */
    public static function useTokenModel(string $tokenModel): void
    {
        static::$tokenModel = $tokenModel;
    }

    /**
     * Get the token model class name.
     *
     * @return class-string<\Laravel\Passport\Token>
     */
    public static function tokenModel(): string
    {
        return static::$tokenModel;
    }

    /**
     * Get a new personal access client model instance.
     */
    public static function token(): Token
    {
        return new static::$tokenModel;
    }

    /**
     * Set the refresh token model class name.
     *
     * @param  class-string<\Laravel\Passport\RefreshToken>  $refreshTokenModel
     */
    public static function useRefreshTokenModel(string $refreshTokenModel): void
    {
        static::$refreshTokenModel = $refreshTokenModel;
    }

    /**
     * Get the refresh token model class name.
     *
     * @return class-string<\Laravel\Passport\RefreshToken>
     */
    public static function refreshTokenModel(): string
    {
        return static::$refreshTokenModel;
    }

    /**
     * Get a new refresh token model instance.
     */
    public static function refreshToken(): RefreshToken
    {
        return new static::$refreshTokenModel;
    }

    /**
     * Specify the callback that should be invoked to generate encryption keys for encrypting JWT tokens.
     *
     * @param  (\Closure(\Illuminate\Contracts\Encryption\Encrypter): string)|null  $callback
     */
    public static function encryptTokensUsing(?Closure $callback): void
    {
        static::$tokenEncryptionKeyCallback = $callback;
    }

    /**
     * Generate an encryption key for encrypting JWT tokens.
     */
    public static function tokenEncryptionKey(Encrypter $encrypter): string
    {
        return is_callable(static::$tokenEncryptionKeyCallback)
            ? (static::$tokenEncryptionKeyCallback)($encrypter)
            : $encrypter->getKey();
    }

    /**
     * Register the views for Passport using conventional names under the given namespace.
     */
    public static function viewNamespace(string $namespace): void
    {
        static::viewPrefix($namespace.'::');
    }

    /**
     * Register the views for Passport using conventional names under the given prefix.
     */
    public static function viewPrefix(string $prefix): void
    {
        static::authorizationView($prefix.'authorize');
        static::deviceAuthorizationView($prefix.'device.authorize');
        static::deviceUserCodeView($prefix.'device.user-code');
    }

    /**
     * Specify which view should be used as the authorization view.
     *
     * @param  (\Closure(array<string, mixed>): (\Symfony\Component\HttpFoundation\Response))|string  $view
     */
    public static function authorizationView(Closure|string $view): void
    {
        app()->singleton(AuthorizationViewResponse::class, fn () => new SimpleViewResponse($view));
    }

    /**
     * Specify which view should be used as the device authorization view.
     *
     * @param  (\Closure(array<string, mixed>): (\Symfony\Component\HttpFoundation\Response))|string  $view
     */
    public static function deviceAuthorizationView(Closure|string $view): void
    {
        app()->singleton(DeviceAuthorizationViewResponse::class, fn () => new SimpleViewResponse($view));
    }

    /**
     * Specify which view should be used as the device user code view.
     *
     * @param  (\Closure(array<string, mixed>): (\Symfony\Component\HttpFoundation\Response))|string  $view
     */
    public static function deviceUserCodeView(Closure|string $view): void
    {
        app()->singleton(DeviceUserCodeViewResponse::class, fn () => new SimpleViewResponse($view));
    }

    /**
     * Configure Passport to not register its routes.
     */
    public static function ignoreRoutes(): void
    {
        static::$registersRoutes = false;
    }

    /**
     * Instruct Passport to enable cookie serialization.
     */
    public static function withCookieSerialization(): void
    {
        static::$unserializesCookies = true;
    }

    /**
     * Instruct Passport to disable cookie serialization.
     */
    public static function withoutCookieSerialization(): void
    {
        static::$unserializesCookies = false;
    }

    /**
     * Instruct Passport to enable cookie encryption.
     */
    public static function withCookieEncryption(): void
    {
        static::$decryptsCookies = true;
    }

    /**
     * Instruct Passport to disable cookie encryption.
     */
    public static function withoutCookieEncryption(): void
    {
        static::$decryptsCookies = false;
    }
}
