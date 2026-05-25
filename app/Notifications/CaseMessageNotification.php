<?php

namespace App\Notifications;

use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class CaseMessageNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public Message $message,
        public CaseFile $case,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $this->message->loadMissing(['sender', 'recipient']);

        $preview = Str::limit($this->message->body, 120);

        $caseRef = $this->case->reference;
        $toLine = $this->message->recipient_id
            ? ' to '.$this->message->recipient->name
            : '';

        return [
            'title' => 'Message on case '.$caseRef,
            'message' => $this->message->sender->name.$toLine.': '.$preview,
            'url' => $this->chatUrlFor($notifiable),
            'type' => 'case_message',
            'case_id' => $this->case->id,
            'case_reference' => $caseRef,
            'message_id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'recipient_id' => $this->message->recipient_id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    private function chatUrlFor(object $notifiable): string
    {
        if ($notifiable->hasRole(UserRole::Client)) {
            return route('client.cases.show', $this->case).'?chat=1';
        }

        if ($notifiable->hasRole(UserRole::Analyst)) {
            return route('analyst.cases.show', $this->case).'?chat=1';
        }

        return route('admin.cases.show', $this->case);
    }
}
