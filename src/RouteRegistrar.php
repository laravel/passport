<?php

namespace Laravel\Passport;

use Illuminate\Contracts\Routing\Registrar as Router;

class RouteRegistrar
{
    /**
     * The router implementation.
     *
     * @var \Illuminate\Contracts\Routing\Registrar
     */
    protected $router;

    /**
     * Create a new route registrar instance.
     *
     * @param  \Illuminate\Contracts\Routing\Registrar  $router
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Register routes for transient tokens, clients, and personal access tokens.
     *
     * @param array $options
     * @return void
     */
    public function all(array $options = [])
    {
        $this->forAuthorization($options);
        $this->forAccessTokens($options);
        $this->forTransientTokens($options);
        $this->forClients($options);
        $this->forPersonalAccessTokens($options);
    }

    /**
     * Register the routes needed for authorization.
     *
     * @param array $options
     * @return void
     */
    public function forAuthorization(array $options = [])
    {
        $this->router->group($this->ensureDefaultOptions($options), function ($router) {
            $router->get('/authorize', [
                'uses' => 'AuthorizationController@authorize',
                'as' => 'passport.authorizations.authorize',
            ]);

            $router->post('/authorize', [
                'uses' => 'ApproveAuthorizationController@approve',
                'as' => 'passport.authorizations.approve',
            ]);

            $router->delete('/authorize', [
                'uses' => 'DenyAuthorizationController@deny',
                'as' => 'passport.authorizations.deny',
            ]);
        });
    }

    /**
     * Register the routes for retrieving and issuing access tokens.
     *
     * @param array $options
     * @return void
     */
    public function forAccessTokens(array $options = [])
    {
        $this->router->post('/token', [
            'uses' => 'AccessTokenController@issueToken',
            'as' => 'passport.token',
            'middleware' => 'throttle',
        ]);

        $this->router->group($this->ensureDefaultOptions($options), function ($router) {
            $router->get('/tokens', [
                'uses' => 'AuthorizedAccessTokenController@forUser',
                'as' => 'passport.tokens.index',
            ]);

            $router->delete('/tokens/{token_id}', [
                'uses' => 'AuthorizedAccessTokenController@destroy',
                'as' => 'passport.tokens.destroy',
            ]);
        });
    }

    /**
     * Register the routes needed for refreshing transient tokens.
     *
     * @param array $options
     * @return void
     */
    public function forTransientTokens(array $options = [])
    {
        $this->router->post('/token/refresh', array_merge($this->ensureDefaultOptions($options), [
            'uses' => 'TransientTokenController@refresh',
            'as' => 'passport.token.refresh',
        ]));
    }

    /**
     * Register the routes needed for managing clients.
     *
     * @param array $options
     * @return void
     */
    public function forClients(array $options = [])
    {
        $this->router->group($this->ensureDefaultOptions($options), function ($router) {
            $router->get('/clients', [
                'uses' => 'ClientController@forUser',
                'as' => 'passport.clients.index',
            ]);

            $router->post('/clients', [
                'uses' => 'ClientController@store',
                'as' => 'passport.clients.store',
            ]);

            $router->put('/clients/{client_id}', [
                'uses' => 'ClientController@update',
                'as' => 'passport.clients.update',
            ]);

            $router->delete('/clients/{client_id}', [
                'uses' => 'ClientController@destroy',
                'as' => 'passport.clients.destroy',
            ]);
        });
    }

    /**
     * Register the routes needed for managing personal access tokens.
     *
     * @param array $options
     * @return void
     */
    public function forPersonalAccessTokens(array $options = [])
    {
        $this->router->group($this->ensureDefaultOptions($options), function ($router) {
            $router->get('/scopes', [
                'uses' => 'ScopeController@all',
                'as' => 'passport.scopes.index',
            ]);

            $router->get('/personal-access-tokens', [
                'uses' => 'PersonalAccessTokenController@forUser',
                'as' => 'passport.personal.tokens.index',
            ]);

            $router->post('/personal-access-tokens', [
                'uses' => 'PersonalAccessTokenController@store',
                'as' => 'passport.personal.tokens.store',
            ]);

            $router->delete('/personal-access-tokens/{token_id}', [
                'uses' => 'PersonalAccessTokenController@destroy',
                'as' => 'passport.personal.tokens.destroy',
            ]);
        });
    }

    /**
     * Ensure default options are properly configured.
     *
     * @param array $options
     * @return array
     */
    protected function ensureDefaultOptions(array $options = [])
    {
        $defaultOptions = [
            'middleware' => ['web', 'auth']
        ];

        return array_merge($defaultOptions, $options);
    }
}
