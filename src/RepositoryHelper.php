<?php

namespace Laravel\Passport;

use RuntimeException;

trait RepositoryHelper
{
    /**
     * Create a new model instance.
     *
     * @param  array  $attributes  Optional array of model attributes.
     * @return mixed
     */
    public function createModel(array $attributes = [])
    {
        if (is_string($model = $this->getModel())) {
            if (! class_exists($class = '\\'.ltrim($model, '\\'))) {
                throw new RuntimeException("Class {$model} does not exist!");
            }

            $model = new $model($attributes);
        }

        return $model;
    }
}
