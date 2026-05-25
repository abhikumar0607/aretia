<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OnboardingReviewRequestedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(public Company $company) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Aretia — New client onboarding review required')
            ->greeting('Hello '.$notifiable->name)
            ->line('A client has completed KYC submission and is ready for review.')
            ->line('Company: '.$this->company->name)
            ->action('Review onboarding', route('admin.onboarding.show', $this->company))
            ->line('Please verify the documents and approve or reject the onboarding request.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New onboarding review required',
            'message' => $this->company->name.' submitted KYC documents and is awaiting approval.',
            'url' => route('admin.onboarding.show', $this->company),
            'type' => 'onboarding_review_requested',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastType(): string
    {
        return 'onboarding.review_requested';
    }
}
