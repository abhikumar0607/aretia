<?php

namespace App\Services;

use App\Enums\EmployeeType;
use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\User;
use App\Notifications\CaseStageCompletedNotification;
use App\Support\CaseWorkflow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CaseStageCompletionNotifyService
{
    public function notifyIfCompleted(CaseFile $case, User $completedBy, string $previousSlug, string $newSlug): void
    {
        if ($previousSlug === $newSlug) {
            return;
        }

        if ($newSlug === CaseWorkflow::SLUG_RESEARCH_DONE) {
            $this->notifyResearchDone($case, $completedBy);

            return;
        }

        if ($newSlug === CaseWorkflow::SLUG_QA_DONE) {
            $this->notifyQaDone($case, $completedBy);
        }
    }

    public function notifyResearchDone(CaseFile $case, User $completedBy): void
    {
        $case->loadMissing(['company', 'order.package', 'analysts']);
        $teamByType = $case->teamByEmployeeType();
        $qaMembers = $teamByType[EmployeeType::Qa->value] ?? collect();
        $qaAssigned = $qaMembers->isNotEmpty();

        $recipients = $this->platformAdmins()
            ->merge($qaMembers)
            ->unique('id')
            ->filter(fn (User $user) => (int) $user->id !== (int) $completedBy->id)
            ->values();

        $this->send($recipients, $case, $completedBy, 'case.research_done', $qaAssigned);
    }

    public function notifyQaDone(CaseFile $case, User $completedBy): void
    {
        $case->loadMissing(['company', 'order.package', 'analysts']);
        $teamByType = $case->teamByEmployeeType();
        $fqaMembers = $teamByType[EmployeeType::Fqa->value] ?? collect();
        $fqaAssigned = $fqaMembers->isNotEmpty();

        $recipients = $this->platformAdmins()
            ->merge($fqaMembers)
            ->unique('id')
            ->filter(fn (User $user) => (int) $user->id !== (int) $completedBy->id)
            ->values();

        $this->send($recipients, $case, $completedBy, 'case.qa_done', $fqaAssigned);
    }

    /**
     * @param  Collection<int, User>  $recipients
     */
    private function send(
        Collection $recipients,
        CaseFile $case,
        User $completedBy,
        string $kind,
        bool $nextRoleAssigned,
    ): void {
        if ($recipients->isEmpty()) {
            return;
        }

        try {
            Notification::send(
                $recipients,
                new CaseStageCompletedNotification($case, $completedBy, $kind, $nextRoleAssigned),
            );
        } catch (\Throwable $e) {
            Log::error('Case stage completion notification failed', [
                'case_id' => $case->id,
                'kind' => $kind,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** @return Collection<int, User> */
    private function platformAdmins(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereIn('role', [UserRole::SuperAdmin->value, UserRole::Admin->value])
            ->orderBy('name')
            ->get();
    }
}
