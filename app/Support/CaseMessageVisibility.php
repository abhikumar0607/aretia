<?php

namespace App\Support;

use App\Enums\MessageChannel;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CaseMessageVisibility
{
    /** Unread case-thread messages for the chat inbox bell. */
    public static function applyCaseThreadInbox(Builder $query, User $user): void
    {
        $userId = (int) $user->id;

        $query
            ->where('channel', MessageChannel::Client->value)
            ->where('sender_id', '!=', $userId);

        if ($user->hasRole(UserRole::Client)) {
            if (! $user->hasPermission(Permission::ChatClient)) {
                $query->whereRaw('1 = 0');

                return;
            }

            $companyIds = CompanyFilter::scopedCompanyIdsForUser($user);
            $query->whereHas('caseFile', fn (Builder $q) => $q
                ->whereIn('company_id', $companyIds));
        } elseif ($user->hasRole(UserRole::Admin) || $user->hasRole(UserRole::SuperAdmin)) {
            if (! $user->hasPermission(Permission::ChatClient)) {
                $query->whereRaw('1 = 0');

                return;
            }
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    /** Unread messages on a case thread a client can mark as read. */
    public static function unreadOnCaseThread(Builder $query, CaseFile $case, User $user): void
    {
        $userId = (int) $user->id;

        $query
            ->where('case_id', $case->id)
            ->where('channel', MessageChannel::Client->value)
            ->whereNull('read_at')
            ->where('sender_id', '!=', $userId);
    }
}
