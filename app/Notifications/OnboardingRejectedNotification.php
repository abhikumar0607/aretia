<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OnboardingRejectedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public Company $company,
        public string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Aretia — Onboarding not approved')
            ->greeting('Hello '.$notifiable->name)
            ->line('Your onboarding for '.$this->company->name.' was not approved at this time.')
            ->line('Reason: '.$this->reason)
            ->action('View onboarding', route('client.onboarding'))
            ->line('You may update your documents and contact support if you have questions.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Onboarding not approved',
            'message' => 'Your application was not approved. Reason: '.\Illuminate\Support\Str::limit($this->reason, 120),
            'url' => route('client.onboarding'),
            'type' => 'onboarding_rejected',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastType(): string
    {
        return 'onboarding.rejected';
    }
}
