<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CaseMessagesRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @param list<int> $messageIds */
    public function __construct(
        public int $caseId,
        public array $messageIds,
        public int $readByUserId,
        public string $readAt,
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('case.'.$this->caseId),
        ];

        $senderIds = Message::query()
            ->whereIn('id', $this->messageIds)
            ->pluck('sender_id')
            ->unique();

        foreach ($senderIds as $senderId) {
            $channels[] = new PrivateChannel('App.Models.User.'.$senderId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'messages.read';
    }

    public function broadcastWith(): array
    {
        return [
            'case_id' => $this->caseId,
            'message_ids' => $this->messageIds,
            'read_by_user_id' => $this->readByUserId,
            'read_at' => $this->readAt,
        ];
    }
}
