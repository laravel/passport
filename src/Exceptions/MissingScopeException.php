<?php

namespace Laravel\Passport\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;

class MissingScopeException extends AuthorizationException
{
    /**
     * The scopes that the user did not have.
     *
     * @var string[]
     */
    protected array $scopes;

    /**
     * Create a new missing scope exception.
     *
     * @param  string[]|string  $scopes
     */
    public function __construct(array|string $scopes = [], string $message = 'Invalid scope(s) provided.')
    {
        parent::__construct($message);

        $this->scopes = Arr::wrap($scopes);
    }

    /**
     * Get the scopes that the user did not have.
     *
     * @return string[]
     */
    public function scopes(): array
    {
        return $this->scopes;
    }
}
