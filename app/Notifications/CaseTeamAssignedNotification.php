<?php

namespace App\Notifications;

use App\Models\CaseFile;
use App\Support\PortalRoute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CaseTeamAssignedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public CaseFile $case,
        public bool $isLead = false,
        public ?string $assignedByName = null,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        if (config('broadcasting.default') && config('broadcasting.default') !== 'null') {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->case->loadMissing(['company', 'order.package']);

        $roleLabel = $notifiable->role->label();
        $leadNote = $this->isLead
            ? ' You are the <strong>lead Analyst</strong> on this case (client chat and primary contact).'
            : '';
        $assignerNote = $this->assignedByName
            ? ' Assigned by <strong>'.e($this->assignedByName).'</strong>.'
            : '';

        return (new MailMessage)
            ->subject('Aretia — Case assigned: '.$this->case->reference)
            ->view('emails.notification', [
                'subject' => 'New case assignment',
                'preheader' => 'You were assigned to case '.$this->case->reference.' as '.$roleLabel.'.',
                'eyebrow' => 'Case assignment',
                'accent' => 'info',
                'title' => 'Hello, '.$notifiable->name,
                'intro' => 'You have been assigned to case <strong>'.$this->case->reference.'</strong> as <strong>'.$roleLabel.'</strong>.'.$assignerNote.$leadNote,
                'highlights' => [
                    'Case' => e($this->case->reference),
                    'Company' => e($this->case->company->name),
                    'Package' => e($this->case->order->package->name),
                    'Your role' => e($roleLabel),
                ],
                'cta_url' => PortalRoute::caseShowRoute($notifiable, $this->case),
                'cta_label' => 'Open case',
                'outro' => 'Sign in to the portal to review documents, update stages, and collaborate with your team.',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $message = $this->case->reference.' — you are on the team as '.$notifiable->employeeTypeLabel().'.';
        if ($this->assignedByName) {
            $message .= ' Assigned by '.$this->assignedByName.'.';
        }

        return [
            'title' => 'Case assigned',
            'message' => $message,
            'url' => PortalRoute::caseShowRoute($notifiable, $this->case),
            'type' => 'case_assigned',
            'case_id' => $this->case->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastType(): string
    {
        return 'case.assigned';
    }
}
