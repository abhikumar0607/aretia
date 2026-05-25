<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'superadmin';
    case Admin = 'admin';
    case Client = 'client';
    case Analyst = 'analyst';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Client => 'Client',
            self::Analyst => 'Analyst',
        };
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::SuperAdmin => 'superadmin.dashboard',
            self::Admin => 'admin.dashboard',
            self::Client => 'client.dashboard',
            self::Analyst => 'analyst.dashboard',
        };
    }
}
