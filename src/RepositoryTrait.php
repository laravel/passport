<?php

namespace Laravel\Passport;

use \Illuminate\Database\Eloquent\Model;

trait RepositoryTrait
{
    /**
     * Create a new instance of the model.
     *
     * @param  array  $data
     *
     * @return Model
     */
    public function modelInstance(array $data = [])
    {
        $class = '\\'.ltrim($this->model, '\\');

        return new $class($data);
    }

    /**
     * Make new provided model
     *
     * @param string $namespace
     * @param array $data
     *
     * @return Model
     */
    public function makeModel(/*string*/ $namespace, array $data = [])
    {
        $class = '\\'.ltrim($namespace, '\\');

        return new $class($data);
    }
}