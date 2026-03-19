<?php

namespace Laravel\Passport\Attributes;

use Attribute;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Laravel\Passport\Http\Middleware\CheckToken;
use Laravel\Passport\Http\Middleware\CheckTokenForAnyScope;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class AuthorizeToken extends Middleware
{
    /**
     * @param  array<string>|string  $scopes
     * @param  array<string>|null  $only
     * @param  array<string>|null  $except
     */
    public function __construct(
        array|string $scopes,
        bool $anyScope = false,
        ?array $only = null,
        ?array $except = null,
    ) {
        parent::__construct(
            $anyScope ? CheckTokenForAnyScope::using($scopes) : CheckToken::using($scopes),
            $only, $except,
        );
    }
}
