<?php


namespace Laravel\Passport;

class UserProvider
{
    public $name;
    public $driver;
    public $model;

    public function __construct($name, $driver, $model)
    {
        $this->name = $name;
        $this->driver = $driver;
        $this->model = $model;
    }
}
