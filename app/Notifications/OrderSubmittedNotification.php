<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\PortalRoute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderSubmittedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->order->loadMissing(['package', 'company', 'user']);

        $highlights = [
            'Order reference' => e($this->order->reference),
            'Company' => e($this->order->company->name),
            'Package' => e($this->order->package->name),
            'Placed by' => e($this->order->user->name),
        ];

        if ($this->order->subject_name) {
            $highlights['Subject'] = e($this->order->subject_name);
        }

        return (new MailMessage)
            ->subject('Aretia — New order awaiting approval')
            ->view('emails.notification', [
                'subject' => 'New order awaiting approval',
                'preheader' => $this->order->company->name.' submitted order '.$this->order->reference.'.',
                'eyebrow' => 'Approval Required',
                'accent' => 'info',
                'title' => 'New client order to review',
                'greeting' => 'Hello '.$notifiable->name.',',
                'intro' => 'A client has placed a new order. Review the details and approve it to create the case file.',
                'highlights' => $highlights,
                'cta_url' => PortalRoute::route('orders.show', $this->order, true, $notifiable),
                'cta_label' => 'Review order',
                'outro' => 'The case will only be created after you approve this order from the portal.',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $this->order->loadMissing(['package', 'company']);

        return [
            'title' => 'New order awaiting approval',
            'message' => $this->order->company->name.' submitted '.$this->order->reference.' ('.$this->order->package->name.').',
            'url' => PortalRoute::route('orders.show', $this->order, true, $notifiable),
            'type' => 'order_submitted',
            'order_id' => $this->order->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastType(): string
    {
        return 'order.submitted';
    }
}
