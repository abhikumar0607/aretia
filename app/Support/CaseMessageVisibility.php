<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CaseMessageVisibility
{
    /** Incoming case messages visible in a client user's chat inbox. */
    public static function applyClientInbox(Builder $query, User $user): void
    {
        $companyIds = CompanyFilter::scopedCompanyIdsForUser($user);
        $userId = (int) $user->id;

        $query
            ->whereHas('caseFile', fn (Builder $q) => $q
                ->whereIn('company_id', $companyIds)
                ->whereNotNull('assigned_to'))
            ->where('sender_id', '!=', $userId)
            ->where(function (Builder $q) use ($userId, $companyIds) {
                $q->where('recipient_id', $userId)
                    ->orWhere(function (Builder $q2) use ($companyIds) {
                        $q2->whereHas('recipient', fn (Builder $r) => $r
                            ->whereIn('company_id', $companyIds)
                            ->where('role', UserRole::Client->value))
                            ->whereHas('sender', fn (Builder $s) => $s
                                ->where('role', '!=', UserRole::Client->value));
                    });
            });
    }

    /** Unread messages on a case a client can mark as read (shared across company teammates). */
    public static function unreadOnCaseForClient(Builder $query, CaseFile $case, User $user): void
    {
        $companyIds = CompanyFilter::scopedCompanyIdsForUser($user);
        $userId = (int) $user->id;

        $query
            ->where('case_id', $case->id)
            ->whereNull('read_at')
            ->where('sender_id', '!=', $userId)
            ->where(function (Builder $q) use ($userId, $companyIds) {
                $q->where('recipient_id', $userId)
                    ->orWhere(function (Builder $q2) use ($companyIds) {
                        $q2->whereHas('recipient', fn (Builder $r) => $r
                            ->whereIn('company_id', $companyIds)
                            ->where('role', UserRole::Client->value))
                            ->whereHas('sender', fn (Builder $s) => $s
                                ->where('role', '!=', UserRole::Client->value));
                    });
            });
    }
}
