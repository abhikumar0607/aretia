<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'superadmin';
    case Admin = 'admin';
    case Client = 'client';
    case Analyst = 'analyst';
    case Qa = 'qa';
    case Fqa = 'fqa';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Client => 'Client',
            self::Analyst => 'Analyst',
            self::Qa => 'QA',
            self::Fqa => 'FQA',
        };
    }

    /** Neutral label for dashboards and charts (no internal role names). */
    public function chartLabel(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Platform admin',
            self::Admin => 'Operations',
            self::Client => 'Company accounts',
            self::Analyst, self::Qa, self::Fqa => 'Case team',
        };
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::SuperAdmin => 'superadmin.dashboard',
            self::Admin => 'admin.dashboard',
            self::Client => 'client.dashboard',
            self::Analyst => 'analyst.dashboard',
            self::Qa => 'qa.dashboard',
            self::Fqa => 'fqa.dashboard',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Analyst => 'badge-employee-analyst',
            self::Qa => 'badge-employee-qa',
            self::Fqa => 'badge-employee-fqa',
            default => 'pill-muted',
        };
    }

    public function isEmployeeRole(): bool
    {
        return in_array($this, self::employeeRoles(), true);
    }

    /** @return list<self> */
    public static function employeeRoles(): array
    {
        return [self::Analyst, self::Qa, self::Fqa];
    }

    /** @return array<string, string> */
    public static function employeeRoleOptions(): array
    {
        $options = [];
        foreach (self::employeeRoles() as $role) {
            $options[$role->value] = $role->label();
        }

        return $options;
    }
}
