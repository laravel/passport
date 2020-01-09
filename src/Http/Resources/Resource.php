<?php

namespace Laravel\Passport\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class Resource extends JsonResource
{
    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        parent::__construct($resource);

        self::$wrap = config('passport.json_resource_wrapper');
    }
}
