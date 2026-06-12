<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationOtpNotification extends Notification
{
    use Queueable;

    public function __construct(public string $otp) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Aretia — Your verification code')
            ->view('emails.notification', [
                'subject' => 'Verify your email',
                'preheader' => 'Your Aretia registration verification code is '.$this->otp.'.',
                'eyebrow' => 'Email verification',
                'accent' => 'primary',
                'title' => 'Your verification code',
                'intro' => 'Use the code below to verify your work email and continue to KYC onboarding. This code expires in <strong>10 minutes</strong>.',
                'highlights' => [
                    'Verification code' => e($this->otp),
                ],
                'outro' => 'If you did not start registration on Aretia, you can ignore this email.',
            ]);
    }
}
