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
        $url = route('client.dashboard');

        return (new MailMessage)
            ->subject('Aretia — Account Activated')
            ->view('emails.notification', [
                'subject' => 'Account Activated',
                'preheader' => 'Your Aretia account is now active.',
                'eyebrow' => 'Account Activated',
                'accent' => 'success',
                'title' => 'You are all set, '.$notifiable->name.'!',
                'intro' => 'Your onboarding is complete and your Aretia account is now <strong>active</strong>. You can place due diligence orders, track cases, and download reports right away.',
                'lines' => [
                    'Our analyst team is ready to support your due diligence needs end-to-end.',
                ],
                'cta_url' => $url,
                'cta_label' => 'Go to Portal',
                'outro' => 'If you have any questions, simply reply to this email and our team will get back to you shortly.',
            ]);
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
