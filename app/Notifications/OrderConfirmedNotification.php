<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->order->loadMissing('package');

        $highlights = [
            'Order reference' => e($this->order->reference),
            'Package' => e($this->order->package->name),
        ];

        if ($this->order->due_date) {
            $highlights['Due date'] = $this->order->due_date->format('d M Y');
        } else {
            $highlights['Due date'] = 'Will be scheduled by our team';
        }

        if ($this->order->subject_name) {
            $highlights['Subject'] = e($this->order->subject_name);
        }

        return (new MailMessage)
            ->subject('Aretia — Order Confirmed')
            ->view('emails.notification', [
                'subject' => 'Order confirmed',
                'preheader' => 'Your order '.$this->order->reference.' has been confirmed.',
                'eyebrow' => 'Order Confirmed',
                'accent' => 'success',
                'title' => 'Your order has been confirmed',
                'greeting' => 'Hello '.$notifiable->name.',',
                'intro' => 'Thank you for placing your due diligence order with Aretia. Our analyst team has received your request and will begin work shortly.',
                'highlights' => $highlights,
                'cta_url' => route('client.orders.show', $this->order),
                'cta_label' => 'View order',
                'outro' => 'You will receive updates as your case progresses. You can track status, message your analyst and download the final report from the portal.',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $this->order->loadMissing('package');

        $message = 'Order '.$this->order->reference.' confirmed.';
        if ($this->order->due_date) {
            $message .= ' Due: '.$this->order->due_date->format('d M Y').'.';
        } else {
            $message .= ' Due date not set yet.';
        }

        return [
            'title' => 'Order confirmed',
            'message' => $message,
            'url' => route('client.orders.show', $this->order),
            'type' => 'order_confirmed',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastType(): string
    {
        return 'order.confirmed';
    }
}
