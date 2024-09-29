<?php

namespace Laravel\Passport;

use DateInterval;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Bridge\PersonalAccessGrant;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Guards\TokenGuard;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Parser as ParserContract;
use Lcobucci\JWT\Token\Parser;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\ImplicitGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;

class PassportServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerPublishing();
        $this->registerCommands();

        $this->deleteCookieOnLogout();
    }

    /**
     * Register the Passport routes.
     */
    protected function registerRoutes(): void
    {
        if (Passport::$registersRoutes) {
            Route::group([
                'as' => 'passport.',
                'prefix' => config('passport.path', 'oauth'),
                'namespace' => 'Laravel\Passport\Http\Controllers',
            ], function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
        }
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $publishesMigrationsMethod = method_exists($this, 'publishesMigrations')
                ? 'publishesMigrations'
                : 'publishes';

            $this->{$publishesMigrationsMethod}([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'passport-migrations');

            $this->publishes([
                __DIR__.'/../config/passport.php' => config_path('passport.php'),
            ], 'passport-config');
        }
    }

    /**
     * Register the Passport Artisan commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\InstallCommand::class,
                Console\ClientCommand::class,
                Console\HashCommand::class,
                Console\KeysCommand::class,
                Console\PurgeCommand::class,
            ]);
        }
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/passport.php', 'passport');

        $this->app->when(AuthorizationController::class)
                ->needs(StatefulGuard::class)
                ->give(fn () => Auth::guard(config('passport.guard', null)));

        $this->app->singleton(ClientRepository::class);

        $this->registerAuthorizationServer();
        $this->registerJWTParser();
        $this->registerResourceServer();
        $this->registerGuard();
    }

    /**
     * Register the authorization server.
     */
    protected function registerAuthorizationServer(): void
    {
        $this->app->singleton(AuthorizationServer::class, function () {
            return tap($this->makeAuthorizationServer(), function (AuthorizationServer $server) {
                $server->setDefaultScope(Passport::$defaultScope);

                $server->enableGrantType(
                    $this->makeAuthCodeGrant(), Passport::tokensExpireIn()
                );

                $server->enableGrantType(
                    $this->makeRefreshTokenGrant(), Passport::tokensExpireIn()
                );

                if (Passport::$passwordGrantEnabled) {
                    $server->enableGrantType(
                        $this->makePasswordGrant(), Passport::tokensExpireIn()
                    );
                }

                $server->enableGrantType(
                    new PersonalAccessGrant, Passport::personalAccessTokensExpireIn()
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
     */
    protected function makeAuthCodeGrant(): AuthCodeGrant
    {
        return tap($this->buildAuthCodeGrant(), function (AuthCodeGrant $grant) {
            $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());
        });
    }

    /**
     * Build the Auth Code grant instance.
     */
    protected function buildAuthCodeGrant(): AuthCodeGrant
    {
        return new AuthCodeGrant(
            $this->app->make(Bridge\AuthCodeRepository::class),
            $this->app->make(Bridge\RefreshTokenRepository::class),
            new DateInterval('PT10M')
        );
    }

    /**
     * Create and configure a Refresh Token grant instance.
     */
    protected function makeRefreshTokenGrant(): RefreshTokenGrant
    {
        $repository = $this->app->make(RefreshTokenRepository::class);

        return tap(new RefreshTokenGrant($repository), function (RefreshTokenGrant $grant) {
            $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());
        });
    }

    /**
     * Create and configure a Password grant instance.
     */
    protected function makePasswordGrant(): PasswordGrant
    {
        return tap(new PasswordGrant(
            $this->app->make(Bridge\UserRepository::class),
            $this->app->make(Bridge\RefreshTokenRepository::class)
        ), function (PasswordGrant $grant) {
            $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());
        });
    }

    /**
     * Create and configure an instance of the Implicit grant.
     */
    protected function makeImplicitGrant(): ImplicitGrant
    {
        return new ImplicitGrant(Passport::tokensExpireIn());
    }

    /**
     * Make the authorization service instance.
     */
    public function makeAuthorizationServer(): AuthorizationServer
    {
        return new AuthorizationServer(
            $this->app->make(Bridge\ClientRepository::class),
            $this->app->make(Bridge\AccessTokenRepository::class),
            $this->app->make(Bridge\ScopeRepository::class),
            $this->makeCryptKey('private'),
            $this->app->make('encrypter')->getKey(),
            Passport::$authorizationServerResponseType
        );
    }

    /**
     * Register the JWT Parser.
     */
    protected function registerJWTParser(): void
    {
        $this->app->singleton(ParserContract::class, fn () => new Parser(new JoseEncoder));
    }

    /**
     * Register the resource server.
     *
     * @return void
     */
    protected function registerResourceServer(): void
    {
        $this->app->singleton(ResourceServer::class, fn ($container) => new ResourceServer(
            $container->make(Bridge\AccessTokenRepository::class),
            $this->makeCryptKey('public')
        ));
    }

    /**
     * Create a CryptKey instance.
     */
    protected function makeCryptKey(string $type): CryptKey
    {
        $key = str_replace('\\n', "\n", config("passport.{$type}_key") ?? '');

        if (! $key) {
            $key = 'file://'.Passport::keyPath('oauth-'.$type.'.key');
        }

        return new CryptKey($key, null, Passport::$validateKeyPermissions && ! windows_os());
    }

    /**
     * Register the token guard.
     */
    protected function registerGuard(): void
    {
        Auth::resolved(function ($auth) {
            $auth->extend('passport', fn ($app, $name, array $config) => tap($this->makeGuard($config), function ($guard) {
                app()->refresh('request', $guard, 'setRequest');
            }));
        });
    }

    /**
     * Make an instance of the token guard.
     *
     * @param  array<string, mixed>  $config
     */
    protected function makeGuard(array $config): TokenGuard
    {
        return new TokenGuard(
            $this->app->make(ResourceServer::class),
            new PassportUserProvider(Auth::createUserProvider($config['provider']), $config['provider']),
            $this->app->make(ClientRepository::class),
            $this->app->make('encrypter'),
            $this->app->make('request')
        );
    }

    /**
     * Register the cookie deletion event handler.
     */
    protected function deleteCookieOnLogout(): void
    {
        Event::listen(Logout::class, function () {
            if (Request::hasCookie(Passport::cookie())) {
                Cookie::queue(Cookie::forget(Passport::cookie()));
            }
        });
    }
}
