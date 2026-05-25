<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OnboardingApprovedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Aretia — Account Activated')
            ->greeting('Hello '.$notifiable->name)
            ->line('Your onboarding is complete and your account is now active.')
            ->action('Go to Portal', route('client.dashboard'))
            ->line('You can now place due diligence orders.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Account activated',
            'message' => 'Your onboarding is complete. You can now use the portal.',
            'url' => route('client.dashboard'),
            'type' => 'onboarding_approved',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastType(): string
    {
        return 'onboarding.approved';
    }
}
