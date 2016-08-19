<?php

namespace Laravel\Passport;

use DateInterval;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\Route;

class Passport
{
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

        $options = array_merge($options, [
            'namespace' => '\Laravel\Passport\Http\Controllers',
        ]);

        Route::group($options, function ($router) use ($callback) {
            $callback(new RouteRegistrar($router));
        });
    }

    /**
     * Instruct Passport to keep revoked tokens pruned.
     *
     * @return static
     */
    public static function pruneRevokedTokens()
    {
        static::$pruneRevokedTokens = true;

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
                            : new DateInterval('P100Y');
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
                            : new DateInterval('P100Y');
        } else {
            static::$refreshTokensExpireAt = $date;
        }

        return new static;
    }
}
