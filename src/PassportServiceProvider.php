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
use Laravel\Passport\Bridge\DeviceCodeRepository;
use Laravel\Passport\Bridge\PersonalAccessBearerTokenResponse;
use Laravel\Passport\Bridge\PersonalAccessGrant;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Contracts\ApprovedDeviceAuthorizationResponse as ApprovedDeviceAuthorizationResponseContract;
use Laravel\Passport\Contracts\DeniedDeviceAuthorizationResponse as DeniedDeviceAuthorizationResponseContract;
use Laravel\Passport\Guards\TokenGuard;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Laravel\Passport\Http\Controllers\DeviceAuthorizationController;
use Laravel\Passport\Http\Responses\ApprovedDeviceAuthorizationResponse;
use Laravel\Passport\Http\Responses\DeniedDeviceAuthorizationResponse;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\DeviceCodeGrant;
use League\OAuth2\Server\Grant\ImplicitGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;

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
            ], function (): void {
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

        $this->app->when([
            AuthorizationController::class,
            DeviceAuthorizationController::class,
        ])->needs(StatefulGuard::class)->give(fn () => Auth::guard(config('passport.guard', null)));

        $this->app->singleton(ClientRepository::class);

        $this->registerResponseBindings();
        $this->registerAuthorizationServer();
        $this->registerResourceServer();
        $this->registerGuard();
    }

    /**
     * Register the response bindings.
     */
    protected function registerResponseBindings(): void
    {
        $this->app->singleton(ApprovedDeviceAuthorizationResponseContract::class, ApprovedDeviceAuthorizationResponse::class);
        $this->app->singleton(DeniedDeviceAuthorizationResponseContract::class, DeniedDeviceAuthorizationResponse::class);
    }

    /**
     * Register the authorization server.
     */
    protected function registerAuthorizationServer(): void
    {
        $this->app->when(PersonalAccessTokenFactory::class)
            ->needs(AuthorizationServer::class)
            ->give(fn () => tap($this->makeAuthorizationServer(new PersonalAccessBearerTokenResponse),
                function (AuthorizationServer $server): void {
                    $server->enableGrantType(new PersonalAccessGrant, Passport::personalAccessTokensExpireIn());
                }
            ));

        $this->app->singleton(AuthorizationServer::class,
            fn () => tap($this->makeAuthorizationServer(), function (AuthorizationServer $server): void {
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
                    new ClientCredentialsGrant, Passport::tokensExpireIn()
                );

                if (Passport::$implicitGrantEnabled) {
                    $server->enableGrantType(
                        $this->makeImplicitGrant(), Passport::tokensExpireIn()
                    );
                }

                if (Route::has('passport.device')) {
                    $server->enableGrantType(
                        $this->makeDeviceCodeGrant(), Passport::tokensExpireIn()
                    );
                }
            })
        );
    }

    /**
     * Create and configure an instance of the Auth Code grant.
     */
    protected function makeAuthCodeGrant(): AuthCodeGrant
    {
        return tap($this->buildAuthCodeGrant(), function (AuthCodeGrant $grant): void {
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
        return tap(new RefreshTokenGrant(
            $this->app->make(RefreshTokenRepository::class)
        ), function (RefreshTokenGrant $grant): void {
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
        ), function (PasswordGrant $grant): void {
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
     * Create and configure an instance of the Device Code grant.
     */
    protected function makeDeviceCodeGrant(): DeviceCodeGrant
    {
        return tap(new DeviceCodeGrant(
            $this->app->make(DeviceCodeRepository::class),
            $this->app->make(RefreshTokenRepository::class),
            new DateInterval('PT10M'),
            route('passport.device'),
            5
        ), function (DeviceCodeGrant $grant) {
            $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());
            $grant->setIncludeVerificationUriComplete(true);
            $grant->setIntervalVisibility(true);
        });
    }

    /**
     * Make the authorization service instance.
     */
    protected function makeAuthorizationServer(?ResponseTypeInterface $responseType = null): AuthorizationServer
    {
        return tap(new AuthorizationServer(
            $this->app->make(Bridge\ClientRepository::class),
            $this->app->make(Bridge\AccessTokenRepository::class),
            $this->app->make(Bridge\ScopeRepository::class),
            $this->makeCryptKey('private'),
            Passport::tokenEncryptionKey($this->app->make('encrypter')),
            $responseType ?? Passport::$authorizationServerResponseType
        ), function (AuthorizationServer $server): void {
            $server->setDefaultScope(Passport::$defaultScope);
            $server->revokeRefreshTokens(Passport::$revokeRefreshTokenAfterUse);
        });
    }

    /**
     * Register the resource server.
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

        return new CryptKey($key, null, Passport::$validateKeyPermissions);
    }

    /**
     * Register the token guard.
     */
    protected function registerGuard(): void
    {
        Auth::resolved(function ($auth): void {
            $auth->extend('passport', fn ($app, $name, array $config) => tap($this->makeGuard($config), function ($guard): void {
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
        Event::listen(Logout::class, function (): void {
            if (Request::hasCookie(Passport::cookie())) {
                Cookie::queue(Cookie::forget(Passport::cookie()));
            }
        });
    }
}
