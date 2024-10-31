<?php

namespace Laravel\Passport;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Str;
use JsonSerializable;

/**
 * @template TValue
 *
 * @implements \Illuminate\Contracts\Support\Arrayable<string, TValue>
 *
 * @property string $accessTokenId
 * @property string $accessToken
 * @property string $tokenType
 * @property int $expiresIn
 */
class PersonalAccessTokenResult implements Arrayable, Jsonable, JsonSerializable
{
    /**
     * The token instance.
     */
    protected ?Token $token = null;

    /**
     * All the attributes set on the personal access token response.
     *
     * @var array<string, TValue>
     */
    protected array $attributes = [];

    /**
     * Create a new result instance.
     *
     * @param  array<string, TValue>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[Str::camel($key)] = $value;
        }
    }

    /**
     * Get the token instance.
     */
    public function getToken(): ?Token
    {
        return $this->token ??= Passport::token()->newQuery()->find($this->accessTokenId);
    }

    /**
     * Get the instance as an array.
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
     * Convert the object to its JSON representation.
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
        return isset($this->attributes[$key]);
    }

    /**
     * Dynamically retrieve the value of an attribute.
     */
    public function __get(string $key): mixed
    {
        if ($key === 'token') {
            return $this->getToken();
        }

        return $this->attributes[$key] ?? null;
    }
}
