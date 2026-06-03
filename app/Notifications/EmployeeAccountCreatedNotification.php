<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeAccountCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public string $plainPassword) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $roleLabel = $notifiable->role->label();
        $loginUrl = url('/'.$notifiable->role->value.'/dashboard');

        return (new MailMessage)
            ->subject('Aretia — Your team account is ready')
            ->view('emails.notification', [
                'subject' => 'Your team account is ready',
                'preheader' => 'Your '.$roleLabel.' account on Aretia has been created.',
                'eyebrow' => 'Welcome to Aretia',
                'accent' => 'primary',
                'title' => 'Hello, '.$notifiable->name.'!',
                'intro' => 'An administrator has created your <strong>'.$roleLabel.'</strong> account on the Aretia due diligence portal. Use the credentials below to sign in.',
                'highlights' => [
                    'Role' => e($roleLabel),
                    'Email' => e($notifiable->email),
                    'Password' => e($this->plainPassword),
                ],
                'cta_url' => route('login'),
                'cta_label' => 'Sign in to portal',
                'outro' => 'After sign-in you will use the <strong>'.$roleLabel.'</strong> portal at <strong>'.$loginUrl.'</strong>. Change your password from <strong>My Profile</strong> after your first login.',
            ]);
    }
}
