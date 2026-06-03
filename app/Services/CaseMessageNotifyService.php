<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\Message;
use App\Models\User;
use App\Notifications\CaseMessageNotification;
use App\Support\CompanyFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class CaseMessageNotifyService
{
    /** @return Collection<int, User> */
    public function notificationRecipients(Message $message): Collection
    {
        $message->loadMissing(['sender', 'caseFile']);
        $case = $message->caseFile;
        $sender = $message->sender;

        if (! $case || ! $sender) {
            return collect();
        }

        if ($sender->hasRole(UserRole::Client)) {
            return $this->caseTeamRecipients($case, (int) $sender->id);
        }

        return CompanyFilter::clientUsersForCompany((int) $case->company_id, (int) $sender->id);
    }

    /** @return list<int> */
    public function notificationRecipientIds(Message $message): array
    {
        return $this->notificationRecipients($message)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function notify(Message $message): void
    {
        $recipients = $this->notificationRecipients($message);

        if ($recipients->isEmpty()) {
            return;
        }

        $message->loadMissing(['sender', 'caseFile']);

        Notification::send($recipients, new CaseMessageNotification($message, $message->caseFile));
    }

    /** @return Collection<int, User> */
    private function caseTeamRecipients(CaseFile $case, int $exceptUserId): Collection
    {
        $case->loadMissing(['analysts', 'assignee']);
        $recipients = $case->analysts;

        if ($case->assignee && ! $recipients->contains('id', $case->assignee->id)) {
            $recipients = $recipients->push($case->assignee);
        }

        return $recipients
            ->filter(fn (User $user) => (int) $user->id !== $exceptUserId)
            ->unique('id')
            ->values();
    }
}
