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
     * Retrieve the router.
     * 
     * @return \Illuminate\Contracts\Routing\Registrar
     */
    public function router()
    {
      return $this->router;
    }

    /**
     * Merge the options specified for a route.
     * 
     * @return Array
     */
    protected function mergeRouteOptions($options = [], $merge = [])
    {
        foreach ($merge as $as => $subOptions) {
            if ($as == $options['as'] && is_array($subOptions)) {
                unset($subOptions['uses']);
                unset($subOptions['as']);

                if (array_key_exists('middleware', $options)) {
                    if (!is_array($options['middleware'])) {
                        $options['middleware'] = explode('|', $options['middleware']);
                    }

                    $options['middleware'] = array_merge($options['middleware'], $subOptions['middleware']);

                    unset($subOptions['middleware']);
                }

                $options = array_merge($options, $subOptions);
            }
        }

        return $options;
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
     * @param  \Illuminate\Contracts\Routing\Registrar  $router
     * @param  Array  $options
     * @return void
     */
    public function forAuthorization($router = null, $options = [])
    {
        $router = $router ?: $this->router;

        $router->group(['middleware' => ['web', 'auth']], function ($r) {
            $r->get('/authorize', $this->mergeRouteOptions([
                'uses' => 'AuthorizationController@authorize',
                'as' => 'passport.authorizations.authorize',
            ], $options));

            $r->post('/authorize', $this->mergeRouteOptions([
                'uses' => 'ApproveAuthorizationController@approve',
                'as' => 'passport.authorizations.approve',
            ], $options));

            $r->delete('/authorize', $this->mergeRouteOptions([
                'uses' => 'DenyAuthorizationController@deny',
                'as' => 'passport.authorizations.deny',
            ], $options));
        });
    }

    /**
     * Register the routes for retrieving and issuing access tokens.
     *
     * @param  \Illuminate\Contracts\Routing\Registrar  $router
     * @param  Array  $options
     * @return void
     */
    public function forAccessTokens($router = null, $options = [])
    {
        $router = $router ?: $this->router;

        $router->post('/token', $this->mergeRouteOptions([
            'uses' => 'AccessTokenController@issueToken',
            'as' => 'passport.token',
            'middleware' => [
              'throttle'
            ],
        ], $options));

        $router->group(['middleware' => ['web', 'auth']], function ($r) {
            $r->get('/tokens', $this->mergeRouteOptions([
                'uses' => 'AuthorizedAccessTokenController@forUser',
                'as' => 'passport.tokens.index',
            ], $options));

            $r->delete('/tokens/{token_id}', $this->mergeRouteOptions([
                'uses' => 'AuthorizedAccessTokenController@destroy',
                'as' => 'passport.tokens.destroy',
            ], $options));
        });
    }

    /**
     * Register the routes needed for refreshing transient tokens.
     *
     * @param  \Illuminate\Contracts\Routing\Registrar  $router
     * @param  Array  $options
     * @return void
     */
    public function forTransientTokens($router = null, $options = [])
    {
        $router = $router ?: $this->router;

        $router->post('/token/refresh', $this->mergeRouteOptions([
            'middleware' => ['web', 'auth'],
            'uses' => 'TransientTokenController@refresh',
            'as' => 'passport.token.refresh',
        ], $options));
    }

    /**
     * Register the routes needed for managing clients.
     *
     * @param  \Illuminate\Contracts\Routing\Registrar  $router
     * @param  Array  $options
     * @return void
     */
    public function forClients($router = null, $options = [])
    {
        $router = $router ?: $this->router;

        $router->group(['middleware' => ['web', 'auth']], function ($r) {
            $r->get('/clients', $this->mergeRouteOptions([
                'uses' => 'ClientController@forUser',
                'as' => 'passport.clients.index',
            ], $options));

            $r->post('/clients', $this->mergeRouteOptions([
                'uses' => 'ClientController@store',
                'as' => 'passport.clients.store',
            ], $options));

            $r->put('/clients/{client_id}', $this->mergeRouteOptions([
                'uses' => 'ClientController@update',
                'as' => 'passport.clients.update',
            ], $options));

            $r->delete('/clients/{client_id}', $this->mergeRouteOptions([
                'uses' => 'ClientController@destroy',
                'as' => 'passport.clients.destroy',
            ], $options));
        });
    }

    /**
     * Register the routes needed for managing personal access tokens.
     *
     * @param  \Illuminate\Contracts\Routing\Registrar  $router
     * @param  Array  $options
     * @return void
     */
    public function forPersonalAccessTokens($router = null, $options = [])
    {
        $router = $router ?: $this->router;

        $router->group(['middleware' => ['web', 'auth']], function ($r) {
            $r->get('/scopes', $this->mergeRouteOptions([
                'uses' => 'ScopeController@all',
                'as' => 'passport.scopes.index',
            ], $options));

            $r->get('/personal-access-tokens', $this->mergeRouteOptions([
                'uses' => 'PersonalAccessTokenController@forUser',
                'as' => 'passport.personal.tokens.index',
            ], $options));

            $r->post('/personal-access-tokens', $this->mergeRouteOptions([
                'uses' => 'PersonalAccessTokenController@store',
                'as' => 'passport.personal.tokens.store',
            ], $options));

            $r->delete('/personal-access-tokens/{token_id}', $this->mergeRouteOptions([
                'uses' => 'PersonalAccessTokenController@destroy',
                'as' => 'passport.personal.tokens.destroy',
            ], $options));
        });
    }
}
