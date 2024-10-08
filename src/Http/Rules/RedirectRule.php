<?php

namespace Laravel\Passport\Http\Rules;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Rule;

/**
 * @deprecated Will be removed in a future Laravel version.
 */
class RedirectRule implements Rule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected Factory $validator
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value): bool
    {
        foreach (explode(',', $value) as $redirect) {
            $validator = $this->validator->make(['redirect' => $redirect], ['redirect' => new UriRule]);

            if ($validator->fails()) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function message(): string
    {
        return 'One or more redirects have an invalid URI format.';
    }
}
