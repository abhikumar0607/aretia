<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class PasswordRules
{
    public static function defaults(): Password
    {
        return Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers();
    }

    public static function hint(): string
    {
        return 'At least 8 characters, with uppercase, lowercase, and a number.';
    }
}
