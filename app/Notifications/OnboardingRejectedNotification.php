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
        $url = route('client.onboarding');

        return (new MailMessage)
            ->subject('Aretia — Onboarding not approved')
            ->view('emails.notification', [
                'subject' => 'Onboarding not approved',
                'preheader' => 'Your onboarding application needs attention.',
                'eyebrow' => 'Action Required',
                'accent' => 'danger',
                'title' => 'Onboarding not approved',
                'greeting' => 'Hello '.$notifiable->name.',',
                'intro' => 'Your onboarding for <strong>'.e($this->company->name).'</strong> was not approved at this time. Please review the reason below and resubmit your documents.',
                'highlights' => [
                    'Reason from our team' => nl2br(e($this->reason)),
                ],
                'cta_url' => $url,
                'cta_label' => 'Update onboarding',
                'outro' => 'You can update your documents and resubmit. If anything is unclear, reply to this email and our team will guide you.',
            ]);
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
