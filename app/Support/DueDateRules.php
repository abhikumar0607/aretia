<?php

namespace App\Support;

class DueDateRules
{
    public static function minDate(): string
    {
        return now()->toDateString();
    }

    public static function formValue(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return $value >= self::minDate() ? $value : '';
    }

    /** @return list<string> */
    public static function optional(): array
    {
        return ['nullable', 'date', 'after_or_equal:'.self::minDate()];
    }

    /** @return list<string> */
    public static function required(): array
    {
        return ['required', 'date', 'after_or_equal:'.self::minDate()];
    }

    /** @return array<string, string> */
    public static function messages(string $attribute = 'due date'): array
    {
        return [
            'after_or_equal' => 'The '.$attribute.' must be today or a future date.',
        ];
    }
}
