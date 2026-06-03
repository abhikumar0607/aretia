<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrictEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('Please enter a valid email address.');

            return;
        }

        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $fail('Please enter a valid email address.');

            return;
        }

        if (! preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i', $value)) {
            $fail('Email must include a valid domain name (e.g. name@company.com).');
        }
    }
}
