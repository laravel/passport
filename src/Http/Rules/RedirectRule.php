<?php

namespace Laravel\Passport\Http\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\Factory;

class RedirectRule implements Rule
{
    /**
     * @var \Illuminate\Contracts\Validation\Factory
     */
    private $validator;

    public function __construct(Factory $validator)
    {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value)
    {
        foreach (explode(',', $value) as $redirect) {
            $validator = $this->validator->make(['redirect' => $redirect], ['redirect' => 'url']);

            if ($validator->fails()) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function message()
    {
        return 'One or more redirects have an invalid url format.';
    }
}
