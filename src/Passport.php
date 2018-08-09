<?php

namespace Laravel\Passport;

use Mockery;
use DateInterval;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\Route;

class Passport
{
    /**
     * Indicates if the implicit grant type is enabled.
     *
     * @var bool|null
     */
    public static $implicitGrantEnabled = false;

    /**
     * Indicates if Passport should revoke existing tokens when issuing a new one.
     *
     * @var bool
     */
    public static $revokeOtherTokens = false;

    /**
     * Indicates if Passport should prune revoked tokens.
     *
     * @var bool
     */
    public static $pruneRevokedTokens = false;

    /**
     * The personal access token client ID.
     *
     * @var int
     */
    public static $personalAccessClientId;

    /**
     * All of the scopes defined for the application.
     *
     * @var array
     */
    public static $scopes = [
        //
    ];

    /**
     * The date when access tokens expire.
     *
     * @var \DateTimeInterface|null
     */
    public static $tokensExpireAt;

    /**
     * The date when refresh tokens expire.
     *
     * @var \DateTimeInterface|null
     */
    public static $refreshTokensExpireAt;

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
     * Indicates if Passport migrations will be run.
     *
     * @var bool
     */
    public static $runsMigrations = true;

    /**
     * Indicates if Passport should unserializes cookies.
     *
     * @var bool
     */
    public static $unserializesCookies = true;

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
     * Binds the Passport routes into the controller.
     *
     * @param  callable|null  $callback
     * @param  array  $options
     * @return void
     */
    public static function routes($callback = null, array $options = [])
    {
        $callback = $callback ?: function ($router) {
            $router->all();
        };

        $defaultOptions = [
            'prefix' => 'oauth',
            'namespace' => '\Laravel\Passport\Http\Controllers',
        ];

        $options = array_merge($defaultOptions, $options);

        Route::group($options, function ($router) use ($callback) {
            $callback(new RouteRegistrar($router));
        });
    }

    /**
     * Instruct Passport to revoke other tokens when a new one is issued.
     *
     * @deprecated since 1.0. Listen to Passport events on token creation instead.
     *
     * @return static
     */
    public static function revokeOtherTokens()
    {
        return new static;
    }

    /**
     * Instruct Passport to keep revoked tokens pruned.
     *
     * @deprecated since 1.0. Listen to Passport events on token creation instead.
     *
     * @return static
     */
    public static function pruneRevokedTokens()
    {
        return new static;
    }

    /**
     * Set the client ID that should be used to issue personal access tokens.
     *
     * @param  int  $clientId
     * @return static
     */
    public static function personalAccessClientId($clientId)
    {
        static::$personalAccessClientId = $clientId;

        return new static;
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

            return;
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
     * @param  \DateTimeInterface|null  $date
     * @return \DateInterval|static
     */
    public static function tokensExpireIn(DateTimeInterface $date = null)
    {
        if (is_null($date)) {
            return static::$tokensExpireAt
                            ? Carbon::now()->diff(static::$tokensExpireAt)
                            : new DateInterval('P1Y');
        }

        static::$tokensExpireAt = $date;

        return new static;
    }

    /**
     * Get or set when refresh tokens expire.
     *
     * @param  \DateTimeInterface|null  $date
     * @return \DateInterval|static
     */
    public static function refreshTokensExpireIn(DateTimeInterface $date = null)
    {
        if (is_null($date)) {
            return static::$refreshTokensExpireAt
                            ? Carbon::now()->diff(static::$refreshTokensExpireAt)
                            : new DateInterval('P1Y');
        }

        static::$refreshTokensExpireAt = $date;

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
     * @param  boolean|null  $ignoreCsrfToken
     * @return boolean|static
     */
    public static function ignoreCsrfToken($ignoreCsrfToken = true)
    {
        static::$ignoreCsrfToken = $ignoreCsrfToken;

        return new static;
    }

    /**
     * Set the current user for the application with the given scopes.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $scopes
     * @param  string  $guard
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public static function actingAs($user, $scopes = [], $guard = 'api')
    {
        $token = Mockery::mock(Passport::tokenModel())->shouldIgnoreMissing(false);

        foreach ($scopes as $scope) {
            $token->shouldReceive('can')->with($scope)->andReturn(true);
        }

        $user->withAccessToken($token);

        app('auth')->guard($guard)->setUser($user);

        app('auth')->shouldUse($guard);

        return $user;
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
     * Configure Passport to not register its migrations.
     *
     * @return static
     */
    public static function ignoreMigrations()
    {
        static::$runsMigrations = false;

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
}
