<?php

namespace Laravel\Passport;

use Carbon\Carbon;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Laravel\Passport\Contracts\AuthorizationViewResponse as AuthorizationViewResponseContract;
use Laravel\Passport\Http\Responses\AuthorizationViewResponse;
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
    public static $defaultScope;

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
    public static $clientUuids = false;

    /**
     * The personal access client model class name.
     *
     * @var string
     */
    public static $personalAccessClientModel = 'Laravel\Passport\PersonalAccessClient';

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
     * Indicates if client secrets will be hashed.
     *
     * @var bool
     */
    public static $hashesClientSecrets = false;

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
     * @param  array|string  $scope
     * @return void
     */
    public static function setDefaultScope($scope)
    {
        static::$defaultScope = is_array($scope) ? implode(' ', $scope) : $scope;
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
        $token = app(self::tokenModel());

        $token->scopes = $scopes;

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
        $token = app(self::tokenModel());

        $token->client_id = $client->getKey();
        $token->setRelation('client', $client);

        $token->scopes = $scopes;

        $mock = Mockery::mock(ResourceServer::class);
        $mock->shouldReceive('validateAuthenticatedRequest')
            ->andReturnUsing(function (ServerRequestInterface $request) use ($token) {
                return $request->withAttribute('oauth_client_id', $token->client->id)
                    ->withAttribute('oauth_access_token_id', $token->id)
                    ->withAttribute('oauth_scopes', $token->scopes);
            });

        app()->instance(ResourceServer::class, $mock);

        $mock = Mockery::mock(TokenRepository::class);
        $mock->shouldReceive('find')->andReturn($token);

        app()->instance(TokenRepository::class, $mock);

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
     * Determine if clients are identified using UUIDs.
     *
     * @return bool
     */
    public static function clientUuids()
    {
        return static::$clientUuids;
    }

    /**
     * Specify if clients are identified using UUIDs.
     *
     * @param  bool  $value
     * @return void
     */
    public static function setClientUuids($value)
    {
        static::$clientUuids = $value;
    }

    /**
     * Set the personal access client model class name.
     *
     * @param  string  $clientModel
     * @return void
     */
    public static function usePersonalAccessClientModel($clientModel)
    {
        static::$personalAccessClientModel = $clientModel;
    }

    /**
     * Get the personal access client model class name.
     *
     * @return string
     */
    public static function personalAccessClientModel()
    {
        return static::$personalAccessClientModel;
    }

    /**
     * Get a new personal access client model instance.
     *
     * @return \Laravel\Passport\PersonalAccessClient
     */
    public static function personalAccessClient()
    {
        return new static::$personalAccessClientModel;
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
     * Configure Passport to hash client credential secrets.
     *
     * @return static
     */
    public static function hashClientSecrets()
    {
        static::$hashesClientSecrets = true;

        return new static;
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
     * Specify which view should be used as the authorization view.
     *
     * @param  callable|string  $view
     * @return void
     */
    public static function authorizationView($view)
    {
        app()->singleton(AuthorizationViewResponseContract::class, function ($app) use ($view) {
            return new AuthorizationViewResponse($view);
        });
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
