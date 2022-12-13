<?php

namespace Laravel\Passport\Http\Responses;

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
	 * The name of the view or the callable used to generate the view.
	 *
	 * @var string
	 */
	protected $client;

	/**
	 * The name of the view or the callable used to generate the view.
	 *
	 * @var string
	 */
	protected $user;

	/**
	 * The name of the view or the callable used to generate the view.
	 *
	 * @var string
	 */
	protected $scopes;

	/**
	 * The name of the view or the callable used to generate the view.
	 *
	 * @var string
	 */
	protected $request;

	/**
	 * The name of the view or the callable used to generate the view.
	 *
	 * @var string
	 */
	protected $authToken;


	/**
	 * Create a new response instance.
	 *
	 * @param  callable|string  $view
	 * @return void
	 */
	public function __construct($view, $client = null, $user = null, $scopes = null, $request = null, $authToken = null)
	{
		$this->view = $view;
		$this->client = $client;
		$this->user = $user;
		$this->scopes = $scopes;
		$this->request = $request;
		$this->authToken = $authToken;
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
			return view($this->view, ['request' => $request]);
		}

		$response = call_user_func($this->view, $this->client, $this->user, $this->scopes, $this->request, $this->authToken);

		if ($response instanceof Responsable) {
			return $response->toResponse($request);
		}

		return $response;
	}
}
