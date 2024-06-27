<?php

namespace Laravel\Passport;

use Illuminate\Support\Traits\ForwardsCalls;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @property  string  oauth_access_token_id
 * @property  string  oauth_client_id
 * @property  string  oauth_user_id
 * @property  string[]  oauth_scopes
 */
class AccessToken
{
    use ResolvesInheritedScopes, ForwardsCalls;

    /**
     * The token instance.
     */
    protected ?Token $token;

    /**
     * All the attributes set on the access token instance.
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * Create a new access token instance.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    /**
     * Create a new access token instance from the incoming PSR-7 request.
     */
    public static function fromPsrRequest(ServerRequestInterface $request): static
    {
        return new static($request->getAttributes());
    }

    /**
     * Determine if the token has a given scope.
     */
    public function can(string $scope): bool
    {
        if (in_array('*', $this->oauth_scopes)) {
            return true;
        }

        $scopes = Passport::$withInheritedScopes
            ? $this->resolveInheritedScopes($scope)
            : [$scope];

        foreach ($scopes as $scope) {
            if (array_key_exists($scope, array_flip($this->oauth_scopes))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the token is missing a given scope.
     */
    public function cant(string $scope): bool
    {
        return ! $this->can($scope);
    }

    /**
     * Determine if the token is a transient JWT token.
     */
    public function transient(): bool
    {
        return false;
    }

    /**
     * Revoke the token instance.
     */
    public function revoke(): bool
    {
        return Passport::token()->whereKey($this->oauth_access_token_id)->forceFill(['revoked' => true])->save();
    }

    /**
     * Get the token instance.
     */
    protected function getToken(): ?Token
    {
        return $this->token ??= Passport::token()->find($this->oauth_access_token_id);
    }

    /**
     * Dynamically determine if an attribute is set.
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]) || isset($this->getToken()?->{$key});
    }

    /**
     * Dynamically retrieve the value of an attribute.
     */
    public function __get(string $key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return $this->getToken()?->{$key};
    }

    /**
     * Pass dynamic methods onto the token instance.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->forwardCallTo($this->getToken(), $method, $parameters);
    }
}
