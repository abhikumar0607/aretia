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
        $url = route('admin.onboarding.show', $this->company);

        return (new MailMessage)
            ->subject('Aretia — New client onboarding review required')
            ->view('emails.notification', [
                'subject' => 'New onboarding review required',
                'preheader' => 'A client has submitted KYC documents for approval.',
                'eyebrow' => 'Review Required',
                'accent' => 'info',
                'title' => 'New onboarding awaits your review',
                'greeting' => 'Hello '.$notifiable->name.',',
                'intro' => 'A client has completed KYC submission and is ready for your review.',
                'highlights' => [
                    'Company' => e($this->company->name),
                    'Status' => 'KYC submitted — awaiting approval',
                ],
                'cta_url' => $url,
                'cta_label' => 'Review onboarding',
                'outro' => 'Please verify the uploaded documents and approve or reject the onboarding request from the portal.',
            ]);
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
