<?php

namespace App\Services;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    private const CACHE_KEY = 'role_permissions.all';

    /** All columns shown in the permissions matrix UI. */
    /** @return list<UserRole> */
    public static function matrixDisplayRoles(): array
    {
        return [
            UserRole::Client,
            UserRole::Analyst,
            UserRole::Qa,
            UserRole::Fqa,
            UserRole::Admin,
            UserRole::SuperAdmin,
        ];
    }

    /** Roles whose permissions can be configured (Admin + Super Admin only). */
    /** @return list<UserRole> */
    public static function configurableStaffRoles(): array
    {
        return [
            UserRole::Admin,
            UserRole::SuperAdmin,
        ];
    }

    public function isStaffRole(UserRole $role): bool
    {
        return in_array($role, self::configurableStaffRoles(), true);
    }

    public function allows(User $user, Permission|string $permission): bool
    {
        $permission = $permission instanceof Permission
            ? $permission
            : Permission::tryFromString($permission);

        if (! $permission) {
            return false;
        }

        if (! $permission->isUniversal() && ! $this->isStaffRole($user->role)) {
            return false;
        }

        $grants = $this->allGrants();
        $roleKey = $user->role->value;
        $permKey = $permission->value;

        if (array_key_exists($permKey, $grants) && array_key_exists($roleKey, $grants[$permKey])) {
            return (bool) $grants[$permKey][$roleKey];
        }

        return $this->defaultGrantFor($permission, $user->role);
    }

    public function allowsAny(User $user, Permission|string ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->allows($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, array<string, bool>> permission => role => granted
     */
    public function allGrants(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $matrix = $this->defaultMatrix();

            $stored = RolePermission::query()->get(['role', 'permission', 'granted']);

            foreach ($stored as $row) {
                if (! isset($matrix[$row->permission][$row->role])) {
                    continue;
                }

                $role = UserRole::from($row->role);
                $perm = Permission::tryFrom($row->permission);

                if ($perm?->isUniversal() || $this->isStaffRole($role)) {
                    $matrix[$row->permission][$row->role] = (bool) $row->granted;
                }
            }

            return $matrix;
        });
    }

    /** @return array<string, bool> */
    public function adminGrants(): array
    {
        $all = $this->allGrants();
        $admin = UserRole::Admin->value;
        $grants = [];

        foreach (Permission::cases() as $permission) {
            $grants[$permission->value] = (bool) ($all[$permission->value][$admin] ?? false);
        }

        return $grants;
    }

    /**
     * @param  array<string, array<string, mixed>>  $input  permissions[perm][role] = 1
     */
    public function syncMatrix(array $input): void
    {
        foreach (Permission::cases() as $permission) {
            if ($permission->isUniversal()) {
                foreach (self::matrixDisplayRoles() as $role) {
                    $granted = ! empty($input[$permission->value][$role->value]);
                    $this->storeGrant($permission, $role, $granted);
                }

                continue;
            }

            foreach (self::configurableStaffRoles() as $role) {
                $granted = ! empty($input[$permission->value][$role->value]);
                $this->storeGrant($permission, $role, $granted);
            }

            foreach (UserRole::employeeRoles() as $role) {
                $this->storeGrant($permission, $role, false);
            }

            $this->storeGrant($permission, UserRole::Client, false);
        }

        $this->clearCache();
    }

    public function seedDefaults(): void
    {
        foreach ($this->defaultMatrix() as $permission => $roles) {
            foreach ($roles as $role => $granted) {
                RolePermission::query()->updateOrCreate(
                    [
                        'role' => $role,
                        'permission' => $permission,
                    ],
                    ['granted' => $granted]
                );
            }
        }

        $this->clearCache();
    }

    /** @return array<string, array<string, bool>> */
    private function defaultMatrix(): array
    {
        $matrix = [];

        foreach (Permission::cases() as $permission) {
            foreach (self::matrixDisplayRoles() as $role) {
                $matrix[$permission->value][$role->value] = $this->defaultGrantFor($permission, $role);
            }
        }

        return $matrix;
    }

    private function defaultGrantFor(Permission $permission, UserRole $role): bool
    {
        if ($permission->isUniversal()) {
            return match ($permission) {
                Permission::ProfileEdit => true,
                Permission::ChatClient => in_array($role, [
                    UserRole::Client,
                    UserRole::Admin,
                    UserRole::SuperAdmin,
                ], true),
                Permission::ReportsView => in_array($role, [
                    UserRole::Client,
                    UserRole::Fqa,
                    UserRole::Admin,
                    UserRole::SuperAdmin,
                ], true),
                Permission::ReportsManage => in_array($role, [
                    UserRole::Fqa,
                    UserRole::Admin,
                    UserRole::SuperAdmin,
                ], true),
                default => true,
            };
        }

        return in_array($role, self::configurableStaffRoles(), true);
    }

    private function storeGrant(Permission $permission, UserRole $role, bool $granted): void
    {
        RolePermission::query()->updateOrCreate(
            [
                'role' => $role->value,
                'permission' => $permission->value,
            ],
            ['granted' => $granted]
        );
    }

    /** @return Collection<int, Permission> */
    public function grantedAdminPermissions(): Collection
    {
        $grants = $this->adminGrants();

        return collect(Permission::cases())
            ->filter(fn (Permission $p) => $grants[$p->value] ?? false)
            ->values();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
