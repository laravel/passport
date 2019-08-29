<?php

namespace Laravel\Passport\Contracts;

use ArrayAccess;
use Illuminate\Contracts\Queue\QueueableEntity;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

interface TokenContract extends ArrayAccess, Arrayable, Jsonable, JsonSerializable, QueueableEntity, UrlRoutable
{
    /**
     * Get the client that the token belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function client();

    /**
     * Get the user that the token belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user();

    /**
     * Determine if the token has a given scope.
     *
     * @param string $scope
     * @return bool
     */
    public function can($scope);

    /**
     * Determine if the token is missing a given scope.
     *
     * @param string $scope
     * @return bool
     */
    public function cant($scope);

    /**
     * Revoke the token instance.
     *
     * @return bool
     */
    public function revoke();

    /**
     * Determine if the token is a transient JWT token.
     *
     * @return bool
     */
    public function transient();

    /**
     * Save the token to the database.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = []);
}
