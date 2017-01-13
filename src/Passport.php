<?php

namespace Laravel\Passport;

use DateInterval;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\Route;

class Passport
{
    /**
     * Indicates if the implicit grant type is enabled.
     *
     * @var boolean|null
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
    public static $personalAccessClient;

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
     * The storage location of the encryption keys.
     *
     * @var string
     */
    public static $keyPath;

    /**
     * Indicates if Passport migrations will be run.
     *
     * @var bool
     */
    public static $runsMigrations = true;

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
     * Get a Passport route registrar.
     *
     * @param  array  $options
     * @return RouteRegistrar
     */
    public static function routes($callback = null, array $options = [])
    {
        $callback = $callback ?: function ($router) {
            $router->all();
        };

        $options = array_merge([
            'namespace' => '\Laravel\Passport\Http\Controllers',
            'prefix' => 'oauth',
        ], $options);

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
    public static function personalAccessClient($clientId)
    {
        static::$personalAccessClient = $clientId;

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
        } else {
            static::$tokensExpireAt = $date;
        }

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
        } else {
            static::$refreshTokensExpireAt = $date;
        }

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
        } else {
            static::$cookie = $cookie;
        }

        return new static;
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
        $file = ltrim($file, "/\\");

        return static::$keyPath
            ? rtrim(static::$keyPath, "/\\").DIRECTORY_SEPARATOR.$file
            : storage_path($file);
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
}
