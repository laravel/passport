<?php

namespace Laravel\Passport\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Laravel\Passport\Contracts\AuthorizationViewResponse as AuthorizationViewResponseContract;

class AuthorizationViewResponse implements AuthorizationViewResponseContract
{
    /**
     * The name of the view or the callable used to generate the view.
     *
     * @var string
     */
    protected $view;

    /**
     * An array of arguments that may be passed to the view response and used in the view.
     *
     * @var string
     */
    protected $parameters;

    /**
     * Create a new response instance.
     *
     * @param  callable|string  $view
     * @return void
     */
    public function __construct($view)
    {
        $this->view = $view;
    }

    /**
     * Add parameters to response.
     *
     * @param  array  $parameters
     * @return $this
     */
    public function withParameters($parameters = [])
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        if (! is_callable($this->view) || is_string($this->view)) {
            return response()->view($this->view, $this->parameters);
        }

        $response = call_user_func($this->view, $this->parameters);

        if ($response instanceof Responsable) {
            return $response->toResponse($request);
        }

        return $response;
    }
}
