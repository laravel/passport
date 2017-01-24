<?php

namespace Laravel\Passport;

use DateInterval;
use Illuminate\Auth\RequestGuard;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Request;
use Laravel\Passport\Guards\TokenGuard;
use Illuminate\Support\ServiceProvider;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ImplicitGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use Laravel\Passport\Bridge\PersonalAccessGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;

class PassportServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'passport');

        $this->deleteCookieOnLogout();

        if ($this->app->runningInConsole()) {
            $this->registerMigrations();

            $this->publishes([
                __DIR__.'/../resources/views' => base_path('resources/views/vendor/passport'),
            ], 'passport-views');

            $this->publishes([
                __DIR__.'/../resources/assets/js/components' => base_path('resources/assets/js/components/passport'),
            ], 'passport-components');

            $this->commands([
                Console\InstallCommand::class,
                Console\ClientCommand::class,
                Console\KeysCommand::class,
            ]);
        }
    }

    /**
     * Register Passport's migration files.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        if (Passport::$runsMigrations) {
            return $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'passport-migrations');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerAuthorizationServer();

        $this->registerResourceServer();

        $this->registerGuard();
    }

    /**
     * Register the authorization server.
     *
     * @return void
     */
    protected function registerAuthorizationServer()
    {
        $this->app->singleton(AuthorizationServer::class, function () {
            return tap($this->makeAuthorizationServer(), function ($server) {
                $server->enableGrantType(
                    $this->makeAuthCodeGrant(), Passport::tokensExpireIn()
                );

                $server->enableGrantType(
                    $this->makeRefreshTokenGrant(), Passport::tokensExpireIn()
                );

                $server->enableGrantType(
                    $this->makePasswordGrant(), Passport::tokensExpireIn()
                );

                $server->enableGrantType(
                    new PersonalAccessGrant, new DateInterval('P10Y')
                );

                $server->enableGrantType(
                    new ClientCredentialsGrant, Passport::tokensExpireIn()
                );

                if (Passport::$implicitGrantEnabled) {
                    $server->enableGrantType(
                        $this->makeImplicitGrant(), Passport::tokensExpireIn()
                    );
                }
            });
        });
    }

    /**
     * Create and configure an instance of the Auth Code grant.
     *
     * @return AuthCodeGrant
     */
    protected function makeAuthCodeGrant()
    {
        return tap($this->buildAuthCodeGrant(), function ($grant) {
            $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());
        });
    }

    /**
     * Build the Auth Code grant instance.
     *
     * @return AuthCodeGrant
     */
    protected function buildAuthCodeGrant()
    {
        return new AuthCodeGrant(
            $this->app->make(Bridge\AuthCodeRepository::class),
            $this->app->make(Bridge\RefreshTokenRepository::class),
            new DateInterval('PT10M')
        );
    }

    /**
     * Create and configure a Refresh Token grant instance.
     *
     * @return RefreshTokenGrant
     */
    protected function makeRefreshTokenGrant()
    {
        $repository = $this->app->make(RefreshTokenRepository::class);

        return tap(new RefreshTokenGrant($repository), function ($grant) {
            $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());
        });
    }

    /**
     * Create and configure a Password grant instance.
     *
     * @return PasswordGrant
     */
    protected function makePasswordGrant()
    {
        $grant = new PasswordGrant(
            $this->app->make(Bridge\UserRepository::class),
            $this->app->make(Bridge\RefreshTokenRepository::class)
        );

        $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());

        return $grant;
    }

    /**
     * Create and configure an instance of the Implicit grant.
     *
     * @return ImplicitGrant
     */
    protected function makeImplicitGrant()
    {
        return new ImplicitGrant(Passport::tokensExpireIn());
    }

    /**
     * Make the authorization service instance.
     *
     * @return AuthorizationServer
     */
    public function makeAuthorizationServer()
    {
        return new AuthorizationServer(
            $this->app->make(Bridge\ClientRepository::class),
            $this->app->make(Bridge\AccessTokenRepository::class),
            $this->app->make(Bridge\ScopeRepository::class),
            'file://'.Passport::keyPath('oauth-private.key'),
            'file://'.Passport::keyPath('oauth-public.key')
        );
    }

    /**
     * Register the resource server.
     *
     * @return void
     */
    protected function registerResourceServer()
    {
        $this->app->singleton(ResourceServer::class, function () {
            return new ResourceServer(
                $this->app->make(Bridge\AccessTokenRepository::class),
                'file://'.Passport::keyPath('oauth-public.key')
            );
        });
    }

    /**
     * Register the token guard.
     *
     * @return void
     */
    protected function registerGuard()
    {
        Auth::extend('passport', function ($app, $name, array $config) {
            return tap($this->makeGuard($config), function ($guard) {
                $this->app->refresh('request', $guard, 'setRequest');
            });
        });
    }

    /**
     * Make an instance of the token guard.
     *
     * @param  array  $config
     * @return RequestGuard
     */
    protected function makeGuard(array $config)
    {
        return new RequestGuard(function ($request) use ($config) {
            return (new TokenGuard(
                $this->app->make(ResourceServer::class),
                Auth::createUserProvider($config['provider']),
                new TokenRepository,
                $this->app->make(ClientRepository::class),
                $this->app->make('encrypter')
            ))->user($request);
        }, $this->app['request']);
    }

    /**
     * Register the cookie deletion event handler.
     *
     * @return void
     */
    protected function deleteCookieOnLogout()
    {
        Event::listen(Logout::class, function () {
            if (Request::hasCookie(Passport::cookie())) {
                Cookie::queue(Cookie::forget(Passport::cookie()));
            }
        });
    }
}
