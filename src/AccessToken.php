<?php

namespace Laravel\Passport;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Traits\ForwardsCalls;
use JsonSerializable;
use Laravel\Passport\Contracts\ScopeAuthorizable;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @template TValue
 *
 * @implements \Illuminate\Contracts\Support\Arrayable<string, TValue>
 *
 * @property string $oauth_access_token_id
 * @property string $oauth_client_id
 * @property string $oauth_user_id
 * @property string[] $oauth_scopes
 */
class AccessToken implements ScopeAuthorizable, Arrayable, Jsonable, JsonSerializable
{
    use ResolvesInheritedScopes, ForwardsCalls;

    /**
     * The token instance.
     */
    protected ?Token $token = null;

    /**
     * All the attributes set on the access token instance.
     *
     * @var array<string, TValue>
     */
    protected array $attributes = [];

    /**
     * Create a new access token instance.
     *
     * @param  array<string, TValue>  $attributes
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
        return in_array('*', $this->oauth_scopes) || $this->scopeExistsIn($scope, $this->oauth_scopes);
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
        return (bool) Passport::token()->newQuery()->whereKey($this->oauth_access_token_id)->update(['revoked' => true]);
    }

    /**
     * Get the token instance.
     */
    protected function getToken(): ?Token
    {
        return $this->token ??= Passport::token()->newQuery()->find($this->oauth_access_token_id);
    }

    /**
     * Convert the access token instance to an array.
     *
     * @return array<string, TValue>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array<string, TValue>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the access token instance to JSON.
     *
     * @param  int  $options
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
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
    public function __get(string $key): mixed
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
