<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;

class PortalRoute
{
    public static function prefix(?User $user = null): string
    {
        $user ??= auth()->user();

        return $user->role->value;
    }

    public static function name(string $suffix, ?User $user = null): string
    {
        return static::prefix($user).'.'.$suffix;
    }

    public static function route(string $suffix, mixed $parameters = [], bool $absolute = true, ?User $user = null): string
    {
        return route(static::name($suffix, $user), $parameters, $absolute);
    }

    public static function is(string $pattern, ?User $user = null): bool
    {
        return request()->routeIs(static::name($pattern, $user));
    }

    public static function caseShowRoute(User $user, mixed $case, bool $withChat = false): string
    {
        $url = static::route('cases.show', $case, true, $user);

        return $withChat ? $url.'?chat=1' : $url;
    }

    public static function employeeRoles(): array
    {
        return UserRole::employeeRoles();
    }
}
