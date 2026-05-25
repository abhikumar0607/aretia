<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('Aretia — Reset your password')
            ->view('emails.notification', [
                'subject' => 'Reset your password',
                'preheader' => 'Use this link to reset your Aretia password.',
                'eyebrow' => 'Password Reset',
                'accent' => 'primary',
                'title' => 'Reset your password',
                'greeting' => 'Hello '.$notifiable->name.',',
                'intro' => 'We received a request to reset the password for your Aretia account. Use the button below to set a new password.',
                'highlights' => [
                    'Account email' => e($notifiable->getEmailForPasswordReset()),
                    'Link valid for' => $expire.' minutes',
                ],
                'cta_url' => $url,
                'cta_label' => 'Reset password',
                'outro' => 'If you did not request a password reset, you can safely ignore this email — your password will not change.',
            ]);
    }
}
