<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderDueDateSetNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public Order $order,
        public bool $isUpdate = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->order->loadMissing(['package', 'caseFile']);

        $subject = $this->isUpdate
            ? 'Aretia — Order due date updated'
            : 'Aretia — Order due date set';

        return (new MailMessage)
            ->subject($subject)
            ->line('Order '.$this->order->reference.' ('.$this->order->package->name.')')
            ->line('Due date: '.$this->order->due_date->format('d M Y'))
            ->action('View order', $this->orderUrl($notifiable));
    }

    public function toArray(object $notifiable): array
    {
        $this->order->loadMissing(['package', 'caseFile']);

        $title = $this->isUpdate ? 'Due date updated' : 'Due date set';
        $message = 'Order '.$this->order->reference.' — due date is '.$this->order->due_date->format('d M Y').'.';

        return [
            'title' => $title,
            'message' => $message,
            'url' => $this->orderUrl($notifiable),
            'type' => 'order_due_date',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastType(): string
    {
        return 'order.due_date';
    }

    private function orderUrl(object $notifiable): string
    {
        if ($notifiable instanceof \App\Models\User && $notifiable->hasRole(\App\Enums\UserRole::Analyst)) {
            $case = $this->order->caseFile;
            if ($case && $case->hasAnalyst($notifiable)) {
                return route('analyst.cases.show', $case);
            }

            return route('analyst.dashboard');
        }

        return route('client.orders.show', $this->order);
    }
}
