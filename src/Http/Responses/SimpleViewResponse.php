<?php

namespace Laravel\Passport\Http\Responses;

use Closure;
use Illuminate\Contracts\Support\Responsable;
use Laravel\Passport\Contracts\AuthorizationViewResponse;
use Laravel\Passport\Contracts\DeviceAuthorizationViewResponse;
use Laravel\Passport\Contracts\DeviceUserCodeViewResponse;

class SimpleViewResponse implements
    AuthorizationViewResponse,
    DeviceAuthorizationViewResponse,
    DeviceUserCodeViewResponse
{
    /**
     * An array of arguments that may be passed to the view response and used in the view.
     *
     * @var array<string, mixed>
     */
    protected array $parameters = [];

    /**
     * Create a new response instance.
     *
     * @param  (\Closure(array<string, mixed>): (\Symfony\Component\HttpFoundation\Response))|string  $view
     */
    public function __construct(
        protected Closure|string $view
    ) {
    }

    /**
     * Add parameters to response.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function withParameters(array $parameters = []): static
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
