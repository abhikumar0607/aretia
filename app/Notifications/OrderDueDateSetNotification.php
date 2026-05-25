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

        $title = $this->isUpdate ? 'Order due date updated' : 'Order due date set';
        $eyebrow = $this->isUpdate ? 'Schedule Updated' : 'Schedule Confirmed';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.notification', [
                'subject' => $title,
                'preheader' => $title.' for order '.$this->order->reference,
                'eyebrow' => $eyebrow,
                'accent' => 'info',
                'title' => $title,
                'greeting' => 'Hello '.$notifiable->name.',',
                'intro' => $this->isUpdate
                    ? 'The due date for your order has been updated by our team.'
                    : 'A due date has been scheduled for your order.',
                'highlights' => [
                    'Order reference' => e($this->order->reference),
                    'Package' => e($this->order->package->name),
                    'Due date' => $this->order->due_date->format('d M Y'),
                ],
                'cta_url' => $this->orderUrl($notifiable),
                'cta_label' => 'View order',
                'outro' => 'Our analyst team is working on your case and will notify you when the report is ready.',
            ]);
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
