<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use App\Notifications\CaseMessageNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class CaseMessageNotifyService
{
    public function __construct(private CaseChatService $caseChat) {}

    /** @return Collection<int, User> */
    public function notificationRecipients(Message $message): Collection
    {
        $message->loadMissing(['sender', 'caseFile']);
        $case = $message->caseFile;
        $sender = $message->sender;

        if (! $case || ! $sender) {
            return collect();
        }

        return $this->caseChat->threadNotificationRecipients($case, $sender);
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
}
