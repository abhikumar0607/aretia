<?php

namespace App\Models;

use App\Enums\OnboardingStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'company_id', 'phone', 'avatar_path', 'is_primary', 'onboarding_status', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'onboarding_status' => OnboardingStatus::class,
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function isAccountActive(): bool
    {
        return $this->is_active;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignedCases(): HasMany
    {
        return $this->hasMany(CaseFile::class, 'assigned_to');
    }

    public function hasRole(UserRole|string $role): bool
    {
        $value = $role instanceof UserRole ? $role->value : $role;

        return $this->role->value === $value;
    }

    public function hasPermission(\App\Enums\Permission|string $permission): bool
    {
        return app(\App\Services\PermissionService::class)->allows($this, $permission);
    }

    public function hasAnyChatPermission(): bool
    {
        return $this->hasPermission(\App\Enums\Permission::ChatClient);
    }

    public function isEmployee(): bool
    {
        return $this->role->isEmployeeRole();
    }

    public function employeeTypeLabel(): string
    {
        return $this->role->label();
    }

    public function displayNameWithRole(): string
    {
        if (! $this->isEmployee()) {
            return $this->name;
        }

        return $this->name.' ('.$this->role->label().')';
    }

    /** @param  Builder<static>  $query */
    public function scopeEmployees(Builder $query): Builder
    {
        return $query->whereIn('role', array_map(
            fn (UserRole $role) => $role->value,
            UserRole::employeeRoles()
        ));
    }

    public function isClientActive(): bool
    {
        return $this->company?->isActive()
            && $this->onboarding_status === OnboardingStatus::Active;
    }

    public function routePrefix(): string
    {
        return $this->role->value;
    }

    public function initials(): string
    {
        $name = trim($this->name);
        if ($name === '') {
            return '?';
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $first = strtoupper(substr($parts[0], 0, 1));
        $second = count($parts) > 1
            ? strtoupper(substr($parts[1], 0, 1))
            : strtoupper(substr($parts[0], 1, 1));

        return $first.$second;
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return asset($this->avatar_path);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
