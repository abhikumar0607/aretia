<?php

namespace App\Enums;

enum EmployeeType: string
{
    case Analyst = 'analyst';
    case Qa = 'qa';
    case Fqa = 'fqa';

    public function label(): string
    {
        return match ($this) {
            self::Analyst => 'Analyst',
            self::Qa => 'QA',
            self::Fqa => 'FQA',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Analyst => 'badge-employee-analyst',
            self::Qa => 'badge-employee-qa',
            self::Fqa => 'badge-employee-fqa',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
