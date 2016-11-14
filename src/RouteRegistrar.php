<?php

namespace Laravel\Passport;

use Illuminate\Contracts\Routing\Registrar as Router;

class RouteRegistrar
{
    /**
     * The router implementation.
     *
     * @var Router
     */
    protected $router;

    /**
     * Create a new route registrar instance.
     *
     * @param  Router  $router
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Register routes for transient tokens, clients, and personal access tokens.
     *
     * @return void
     */
    public function all()
    {
        $this->forAuthorization();
        $this->forAccessTokens();
        $this->forTransientTokens();
        $this->forClients();
        $this->forPersonalAccessTokens();
    }

    /**
     * Register the routes needed for authorization.
     *
     * @return void
     */
    public function forAuthorization()
    {
        $this->router->group(['middleware' => ['web', 'auth']], function ($router) {
            $router->get('/oauth/authorize', [
                'uses' => 'AuthorizationController@authorize',
            ]);

            $router->post('/oauth/authorize', [
                'uses' => 'ApproveAuthorizationController@approve',
            ]);

            $router->delete('/oauth/authorize', [
                'uses' => 'DenyAuthorizationController@deny',
            ]);
        });
    }

    /**
     * Register the routes for retrieving and issuing access tokens.
     *
     * @return void
     */
    public function forAccessTokens()
    {
        $this->router->post('/oauth/token', [
            'uses' => 'AccessTokenController@issueToken',
            'middleware' => 'throttle'
        ]);

        $this->router->group(['middleware' => ['web', 'auth']], function ($router) {
            $router->get('/oauth/tokens', [
                'uses' => 'AuthorizedAccessTokenController@forUser',
            ]);

            $router->delete('/oauth/tokens/{token_id}', [
                'uses' => 'AuthorizedAccessTokenController@destroy',
            ]);
        });
    }

    /**
     * Register the routes needed for refreshing transient tokens.
     *
     * @return void
     */
    public function forTransientTokens()
    {
        $this->router->post('/oauth/token/refresh', [
            'middleware' => ['web', 'auth'],
            'uses' => 'TransientTokenController@refresh',
        ]);
    }

    /**
     * Register the routes needed for managing clients.
     *
     * @return void
     */
    public function forClients()
    {
        $this->router->group(['middleware' => ['web', 'auth']], function ($router) {
            $router->get('/oauth/clients', [
                'uses' => 'ClientController@forUser',
            ]);

            $router->post('/oauth/clients', [
                'uses' => 'ClientController@store',
            ]);

            $router->put('/oauth/clients/{client_id}', [
                'uses' => 'ClientController@update',
            ]);

            $router->delete('/oauth/clients/{client_id}', [
                'uses' => 'ClientController@destroy',
            ]);
        });
    }

    /**
     * Register the routes needed for managing personal access tokens.
     *
     * @return void
     */
    public function forPersonalAccessTokens()
    {
        $this->router->group(['middleware' => ['web', 'auth']], function ($router) {
            $router->get('/oauth/scopes', [
                'uses' => 'ScopeController@all',
            ]);

            $router->get('/oauth/personal-access-tokens', [
                'uses' => 'PersonalAccessTokenController@forUser',
            ]);

            $router->post('/oauth/personal-access-tokens', [
                'uses' => 'PersonalAccessTokenController@store',
            ]);

            $router->delete('/oauth/personal-access-tokens/{token_id}', [
                'uses' => 'PersonalAccessTokenController@destroy',
            ]);
        });
    }
}
