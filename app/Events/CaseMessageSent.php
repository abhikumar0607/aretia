<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CaseMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('case.'.$this->message->case_id),
        ];

        if ($this->message->recipient_id) {
            $channels[] = new PrivateChannel('App.Models.User.'.$this->message->recipient_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $this->message->loadMissing(['sender', 'recipient', 'caseFile']);

        return [
            'id' => $this->message->id,
            'case_id' => $this->message->case_id,
            'case_reference' => $this->message->caseFile?->reference,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender->name,
            'recipient_id' => $this->message->recipient_id,
            'recipient_name' => $this->message->recipient?->name,
            'body' => $this->message->body,
            'created_at' => $this->message->created_at->toIso8601String(),
            'created_at_label' => $this->message->created_at->format('d M Y, H:i'),
            'read_at' => $this->message->read_at?->toIso8601String(),
            'is_read' => $this->message->read_at !== null,
        ];
    }
}
