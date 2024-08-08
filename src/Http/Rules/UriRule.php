<?php

namespace Laravel\Passport\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UriRule implements ValidationRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            $fail('The :attribute field must be a valid URI.');
        }
    }
}
