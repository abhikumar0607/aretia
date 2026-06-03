<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderRejectedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->order->loadMissing('package');

        return (new MailMessage)
            ->subject('Aretia — Order not approved')
            ->view('emails.notification', [
                'subject' => 'Order not approved',
                'preheader' => 'Order '.$this->order->reference.' was not approved.',
                'eyebrow' => 'Order Update',
                'accent' => 'danger',
                'title' => 'Your order was not approved',
                'greeting' => 'Hello '.$notifiable->name.',',
                'intro' => 'Your order <strong>'.e($this->order->reference).'</strong> ('.e($this->order->package->name).') was not approved at this time.',
                'highlights' => [
                    'Reason from our team' => nl2br(e($this->reason)),
                ],
                'cta_url' => route('client.orders.show', $this->order),
                'cta_label' => 'View order',
                'outro' => 'If you have questions, contact our team or place a new order with updated details.',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $this->order->loadMissing('package');

        return [
            'title' => 'Order not approved',
            'message' => 'Order '.$this->order->reference.' was not approved.',
            'url' => route('client.orders.show', $this->order),
            'type' => 'order_rejected',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastType(): string
    {
        return 'order.rejected';
    }
}
