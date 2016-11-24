<?php

namespace Laravel\Passport\Exceptions;

use Exception;

class InvalidScopesException extends Exception
{
    /**
     * The scope(s) that failed authorization.
     *
     * @var array
     */
    protected $scopes;

    /**
     * Create a new invalid scope exception.
     *
     * @param  array|string  $scopes
     * @param  string  $message
     * @return void
     */
    public function __construct($scopes = [], $message = 'Invalid scope(s) provided.')
    {
        parent::__construct($message);
        $this->scopes = is_array($scopes) ? $scopes : [$scopes];
    }

    /**
     * Get the scopes that were failed authorization.
     *
     * @return array
     */
    public function scopes()
    {
        return $this->scopes;
    }
}
