<?php

namespace App\Services;

use App\Enums\MessageChannel;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\User;
use App\Support\CompanyFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CaseChatService
{
    public function canUseCaseChat(CaseFile $case, User $viewer): bool
    {
        if (! $viewer->hasPermission(Permission::ChatClient)) {
            return false;
        }

        if ($viewer->hasRole(UserRole::Admin) || $viewer->hasRole(UserRole::SuperAdmin)) {
            return (bool) $case->assigned_to;
        }

        if ($viewer->hasRole(UserRole::Client)) {
            return $case->assigned_to
                && CompanyFilter::userCanAccessCompany($viewer, $case->company_id);
        }

        return false;
    }

    public function canSendCaseChat(User $sender): bool
    {
        if (! $sender->hasPermission(Permission::ChatClient)) {
            return false;
        }

        return $sender->hasRole(UserRole::Client)
            || $sender->hasRole(UserRole::Admin)
            || $sender->hasRole(UserRole::SuperAdmin);
    }

    public function threadTitle(CaseFile $case): string
    {
        return 'Case chat';
    }

    public function threadSubtitle(CaseFile $case): string
    {
        $parts = array_filter([
            $case->reference,
            $case->company?->name,
        ]);

        return implode(' · ', $parts);
    }

    public function applyCaseThreadVisibility(Builder $query, CaseFile $case, User $viewer): void
    {
        if (! $this->canUseCaseChat($case, $viewer)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where('channel', MessageChannel::Client->value);
    }

    /** @return Collection<int, User> */
    public function threadNotificationRecipients(CaseFile $case, User $sender): Collection
    {
        if ($sender->hasRole(UserRole::Client)) {
            return $this->adminRecipients((int) $sender->id);
        }

        if ($sender->hasRole(UserRole::Admin) || $sender->hasRole(UserRole::SuperAdmin)) {
            return CompanyFilter::clientUsersForCompany((int) $case->company_id, (int) $sender->id);
        }

        return collect();
    }

    /** @return Collection<int, User> */
    private function adminRecipients(int $exceptUserId = 0): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereIn('role', [UserRole::Admin->value, UserRole::SuperAdmin->value])
            ->orderByRaw('CASE role WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END', [
                UserRole::SuperAdmin->value,
                UserRole::Admin->value,
            ])
            ->orderBy('id')
            ->get()
            ->filter(fn (User $user) => $user->hasPermission(Permission::ChatClient)
                && (int) $user->id !== $exceptUserId)
            ->values();
    }
}
