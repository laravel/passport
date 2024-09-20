<?php

namespace Laravel\Passport;

use Carbon\Carbon;
use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Laravel\Passport\Contracts\AuthorizationViewResponse;
use Laravel\Passport\Contracts\DeviceAuthorizationViewResponse;
use Laravel\Passport\Contracts\DeviceUserCodeViewResponse;
use Laravel\Passport\Http\Responses\SimpleViewResponse;
use League\OAuth2\Server\ResourceServer;
use Mockery;
use Psr\Http\Message\ServerRequestInterface;

class Passport
{
    /**
     * Indicates if Passport should validate the permissions of its encryption keys.
     *
     * @var bool
     */
    public static $validateKeyPermissions = false;

    /**
     * Indicates if the implicit grant type is enabled.
     *
     * @var bool|null
     */
    public static $implicitGrantEnabled = false;

    /**
     * Indicates if the password grant type is enabled.
     *
     * @var bool|null
     */
    public static $passwordGrantEnabled = false;

    /**
     * The default scope.
     *
     * @var string
     */
    public static $defaultScope = '';

    /**
     * All of the scopes defined for the application.
     *
     * @var array
     */
    public static $scopes = [
        //
    ];

    /**
     * The interval when access tokens expire.
     *
     * @var \DateInterval|null
     */
    public static $tokensExpireIn;

    /**
     * The date when refresh tokens expire.
     *
     * @var \DateInterval|null
     */
    public static $refreshTokensExpireIn;

    /**
     * The date when personal access tokens expire.
     *
     * @var \DateInterval|null
     */
    public static $personalAccessTokensExpireIn;

    /**
     * The name for API token cookies.
     *
     * @var string
     */
    public static $cookie = 'laravel_token';

    /**
     * Indicates if Passport should ignore incoming CSRF tokens.
     *
     * @var bool
     */
    public static $ignoreCsrfToken = false;

    /**
     * The storage location of the encryption keys.
     *
     * @var string
     */
    public static $keyPath;

    /**
     * The access token entity class name.
     *
     * @var string
     */
    public static $accessTokenEntity = 'Laravel\Passport\Bridge\AccessToken';

    /**
     * The auth code model class name.
     *
     * @var string
     */
    public static $authCodeModel = 'Laravel\Passport\AuthCode';

    /**
     * The device code model class name.
     *
     * @var class-string<\Laravel\Passport\DeviceCode>
     */
    public static string $deviceCodeModel = DeviceCode::class;

    /**
     * The client model class name.
     *
     * @var string
     */
    public static $clientModel = 'Laravel\Passport\Client';

    /**
     * Indicates if client's are identified by UUIDs.
     *
     * @var bool
     */
    public static $clientUuids = true;

    /**
     * The token model class name.
     *
     * @var string
     */
    public static $tokenModel = 'Laravel\Passport\Token';

    /**
     * The refresh token model class name.
     *
     * @var string
     */
    public static $refreshTokenModel = 'Laravel\Passport\RefreshToken';

    /**
     * Indicates if Passport should unserializes cookies.
     *
     * @var bool
     */
    public static $unserializesCookies = false;

    /**
     * Indicates if Passport should decrypt cookies.
     *
     * @var bool
     */
    public static $decryptsCookies = true;

    /**
     * The callback that should be used to generate JWT encryption keys.
     *
     * @var callable
     */
    public static $tokenEncryptionKeyCallback;

    /**
     * Indicates the scope should inherit its parent scope.
     *
     * @var bool
     */
    public static $withInheritedScopes = false;

    /**
     * The authorization server response type.
     *
     * @var \League\OAuth2\Server\ResponseTypes\ResponseTypeInterface|null
     */
    public static $authorizationServerResponseType;

    /**
     * Indicates if Passport routes will be registered.
     *
     * @var bool
     */
    public static $registersRoutes = true;

    /**
     * Enable the implicit grant type.
     *
     * @return static
     */
    public static function enableImplicitGrant()
    {
        static::$implicitGrantEnabled = true;

        return new static;
    }

    /**
     * Enable the password grant type.
     *
     * @return static
     */
    public static function enablePasswordGrant()
    {
        static::$passwordGrantEnabled = true;

        return new static;
    }

    /**
     * Set the default scope(s). Multiple scopes may be an array or specified delimited by spaces.
     *
     * @deprecated Use defaultScopes.
     *
     * @param  array|string  $scope
     * @return void
     */
    public static function setDefaultScope($scope)
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
     * @return array
     */
    public static function scopeIds()
    {
        return static::scopes()->pluck('id')->values()->all();
    }

    /**
     * Determine if the given scope has been defined.
     *
     * @param  string  $id
     * @return bool
     */
    public static function hasScope($id)
    {
        return $id === '*' || array_key_exists($id, static::$scopes);
    }

    /**
     * Get all of the scopes defined for the application.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function scopes()
    {
        return collect(static::$scopes)->map(function ($description, $id) {
            return new Scope($id, $description);
        })->values();
    }

    /**
     * Get all of the scopes matching the given IDs.
     *
     * @param  array  $ids
     * @return array
     */
    public static function scopesFor(array $ids)
    {
        return collect($ids)->map(function ($id) {
            if (isset(static::$scopes[$id])) {
                return new Scope($id, static::$scopes[$id]);
            }
        })->filter()->values()->all();
    }

    /**
     * Define the scopes for the application.
     *
     * @param  array  $scopes
     * @return void
     */
    public static function tokensCan(array $scopes)
    {
        static::$scopes = $scopes;
    }

    /**
     * Get or set when access tokens expire.
     *
     * @param  \DateTimeInterface|\DateInterval|null  $date
     * @return \DateInterval|static
     */
    public static function tokensExpireIn(DateTimeInterface|DateInterval $date = null)
    {
        if (is_null($date)) {
            return static::$tokensExpireIn ?? new DateInterval('P1Y');
        }

        static::$tokensExpireIn = $date instanceof DateTimeInterface
            ? Carbon::now()->diff($date)
            : $date;

        return new static;
    }

    /**
     * Get or set when refresh tokens expire.
     *
     * @param  \DateTimeInterface|\DateInterval|null  $date
     * @return \DateInterval|static
     */
    public static function refreshTokensExpireIn(DateTimeInterface|DateInterval $date = null)
    {
        if (is_null($date)) {
            return static::$refreshTokensExpireIn ?? new DateInterval('P1Y');
        }

        static::$refreshTokensExpireIn = $date instanceof DateTimeInterface
            ? Carbon::now()->diff($date)
            : $date;

        return new static;
    }

    /**
     * Get or set when personal access tokens expire.
     *
     * @param  \DateTimeInterface|\DateInterval|null  $date
     * @return \DateInterval|static
     */
    public static function personalAccessTokensExpireIn(DateTimeInterface|DateInterval $date = null)
    {
        if (is_null($date)) {
            return static::$personalAccessTokensExpireIn ?? new DateInterval('P1Y');
        }

        static::$personalAccessTokensExpireIn = $date instanceof DateTimeInterface
            ? Carbon::now()->diff($date)
            : $date;

        return new static;
    }

    /**
     * Get or set the name for API token cookies.
     *
     * @param  string|null  $cookie
     * @return string|static
     */
    public static function cookie($cookie = null)
    {
        if (is_null($cookie)) {
            return static::$cookie;
        }

        static::$cookie = $cookie;

        return new static;
    }

    /**
     * Indicate that Passport should ignore incoming CSRF tokens.
     *
     * @param  bool  $ignoreCsrfToken
     * @return static
     */
    public static function ignoreCsrfToken($ignoreCsrfToken = true)
    {
        static::$ignoreCsrfToken = $ignoreCsrfToken;

        return new static;
    }

    /**
     * Set the current user for the application with the given scopes.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|\Laravel\Passport\HasApiTokens  $user
     * @param  array  $scopes
     * @param  string  $guard
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public static function actingAs($user, $scopes = [], $guard = 'api')
    {
        $token = new AccessToken([
            'oauth_user_id' => $user->getKey(),
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
     * @param  \Laravel\Passport\Client  $client
     * @param  array  $scopes
     * @param  string  $guard
     * @return \Laravel\Passport\Client
     */
    public static function actingAsClient($client, $scopes = [], $guard = 'api')
    {
        $mock = Mockery::mock(ResourceServer::class);
        $mock->shouldReceive('validateAuthenticatedRequest')
            ->andReturnUsing(function (ServerRequestInterface $request) use ($client, $scopes) {
                return $request->withAttribute('oauth_client_id', $client->getKey())
                    ->withAttribute('oauth_scopes', $scopes);
            });

        app()->instance(ResourceServer::class, $mock);

        app('auth')->guard($guard)->setClient($client);

        app('auth')->shouldUse($guard);

        return $client;
    }

    /**
     * Set the storage location of the encryption keys.
     *
     * @param  string  $path
     * @return void
     */
    public static function loadKeysFrom($path)
    {
        static::$keyPath = $path;
    }

    /**
     * The location of the encryption keys.
     *
     * @param  string  $file
     * @return string
     */
    public static function keyPath($file)
    {
        $file = ltrim($file, '/\\');

        return static::$keyPath
            ? rtrim(static::$keyPath, '/\\').DIRECTORY_SEPARATOR.$file
            : storage_path($file);
    }

    /**
     * Set the access token entity class name.
     *
     * @param  string  $accessTokenEntity
     * @return void
     */
    public static function useAccessTokenEntity($accessTokenEntity)
    {
        static::$accessTokenEntity = $accessTokenEntity;
    }

    /**
     * Set the auth code model class name.
     *
     * @param  string  $authCodeModel
     * @return void
     */
    public static function useAuthCodeModel($authCodeModel)
    {
        static::$authCodeModel = $authCodeModel;
    }

    /**
     * Get the auth code model class name.
     *
     * @return string
     */
    public static function authCodeModel()
    {
        return static::$authCodeModel;
    }

    /**
     * Get a new auth code model instance.
     *
     * @return \Laravel\Passport\AuthCode
     */
    public static function authCode()
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
     * @param  string  $clientModel
     * @return void
     */
    public static function useClientModel($clientModel)
    {
        static::$clientModel = $clientModel;
    }

    /**
     * Get the client model class name.
     *
     * @return string
     */
    public static function clientModel()
    {
        return static::$clientModel;
    }

    /**
     * Get a new client model instance.
     *
     * @return \Laravel\Passport\Client
     */
    public static function client()
    {
        return new static::$clientModel;
    }

    /**
     * Set the token model class name.
     *
     * @param  string  $tokenModel
     * @return void
     */
    public static function useTokenModel($tokenModel)
    {
        static::$tokenModel = $tokenModel;
    }

    /**
     * Get the token model class name.
     *
     * @return string
     */
    public static function tokenModel()
    {
        return static::$tokenModel;
    }

    /**
     * Get a new personal access client model instance.
     *
     * @return \Laravel\Passport\Token
     */
    public static function token()
    {
        return new static::$tokenModel;
    }

    /**
     * Set the refresh token model class name.
     *
     * @param  string  $refreshTokenModel
     * @return void
     */
    public static function useRefreshTokenModel($refreshTokenModel)
    {
        static::$refreshTokenModel = $refreshTokenModel;
    }

    /**
     * Get the refresh token model class name.
     *
     * @return string
     */
    public static function refreshTokenModel()
    {
        return static::$refreshTokenModel;
    }

    /**
     * Get a new refresh token model instance.
     *
     * @return \Laravel\Passport\RefreshToken
     */
    public static function refreshToken()
    {
        return new static::$refreshTokenModel;
    }

    /**
     * Specify the callback that should be invoked to generate encryption keys for encrypting JWT tokens.
     *
     * @param  callable  $callback
     * @return static
     */
    public static function encryptTokensUsing($callback)
    {
        static::$tokenEncryptionKeyCallback = $callback;

        return new static;
    }

    /**
     * Generate an encryption key for encrypting JWT tokens.
     *
     * @param  \Illuminate\Contracts\Encryption\Encrypter  $encrypter
     * @return string
     */
    public static function tokenEncryptionKey(Encrypter $encrypter)
    {
        return is_callable(static::$tokenEncryptionKeyCallback) ?
            (static::$tokenEncryptionKeyCallback)($encrypter) :
            $encrypter->getKey();
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
     */
    public static function authorizationView(Closure|string $view): void
    {
        app()->singleton(AuthorizationViewResponse::class, fn () => new SimpleViewResponse($view));
    }

    /**
     * Specify which view should be used as the device authorization view.
     */
    public static function deviceAuthorizationView(Closure|string $view): void
    {
        app()->singleton(DeviceAuthorizationViewResponse::class, fn () => new SimpleViewResponse($view));
    }

    /**
     * Specify which view should be used as the device user code view.
     */
    public static function deviceUserCodeView(Closure|string $view): void
    {
        app()->singleton(DeviceUserCodeViewResponse::class, fn () => new SimpleViewResponse($view));
    }

    /**
     * Configure Passport to not register its routes.
     *
     * @return static
     */
    public static function ignoreRoutes()
    {
        static::$registersRoutes = false;

        return new static;
    }

    /**
     * Instruct Passport to enable cookie serialization.
     *
     * @return static
     */
    public static function withCookieSerialization()
    {
        static::$unserializesCookies = true;

        return new static;
    }

    /**
     * Instruct Passport to disable cookie serialization.
     *
     * @return static
     */
    public static function withoutCookieSerialization()
    {
        static::$unserializesCookies = false;

        return new static;
    }

    /**
     * Instruct Passport to enable cookie encryption.
     *
     * @return static
     */
    public static function withCookieEncryption()
    {
        static::$decryptsCookies = true;

        return new static;
    }

    /**
     * Instruct Passport to disable cookie encryption.
     *
     * @return static
     */
    public static function withoutCookieEncryption()
    {
        static::$decryptsCookies = false;

        return new static;
    }
}
