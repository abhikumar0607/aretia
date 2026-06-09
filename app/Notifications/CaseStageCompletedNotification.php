<?php

namespace App\Notifications;

use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\User;
use App\Support\PortalRoute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CaseStageCompletedNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public CaseFile $case,
        public User $completedBy,
        public string $kind,
        public bool $nextRoleAssigned,
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
        $copy = $this->copyFor($notifiable);

        return (new MailMessage)
            ->subject('Aretia — '.$copy['subject'].' — '.$this->case->reference)
            ->view('emails.notification', [
                'subject' => $copy['subject'],
                'preheader' => $copy['preheader'],
                'eyebrow' => $copy['eyebrow'],
                'accent' => 'success',
                'title' => $copy['title'],
                'intro' => $copy['intro'],
                'highlights' => array_filter([
                    'Case' => e($this->case->reference),
                    'Company' => e($this->case->company->name),
                    'Package' => e($this->case->order->package->name),
                    'Completed by' => e($this->completedBy->name).' ('.$this->completedBy->role->label().')',
                    'Stage' => e($copy['stage_label']),
                ]),
                'cta_url' => PortalRoute::caseShowRoute($notifiable, $this->case),
                'cta_label' => 'Open case',
                'outro' => $copy['outro'],
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $copy = $this->copyFor($notifiable);

        return [
            'title' => $copy['subject'],
            'message' => $copy['message'],
            'url' => PortalRoute::caseShowRoute($notifiable, $this->case),
            'type' => $this->kind,
            'case_id' => $this->case->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastType(): string
    {
        return $this->kind;
    }

    /**
     * @return array{subject: string, preheader: string, eyebrow: string, title: string, intro: string, message: string, outro: string, stage_label: string}
     */
    private function copyFor(object $notifiable): array
    {
        $isAdmin = $notifiable instanceof User
            && ($notifiable->hasRole(UserRole::SuperAdmin) || $notifiable->hasRole(UserRole::Admin));

        if ($this->kind === 'case.research_done') {
            return $this->researchDoneCopy($isAdmin);
        }

        return $this->qaDoneCopy($isAdmin);
    }

    /**
     * @return array{subject: string, preheader: string, eyebrow: string, title: string, intro: string, message: string, outro: string, stage_label: string}
     */
    private function researchDoneCopy(bool $isAdmin): array
    {
        $ref = $this->case->reference;
        $name = e($this->completedBy->name);
        $assignNote = ' QA is not assigned to this case yet — please assign QA first.';

        if ($isAdmin) {
            return [
                'subject' => 'Analyst completed research',
                'preheader' => 'Analyst '.$this->completedBy->name.' marked research done on '.$ref.'.',
                'eyebrow' => 'Case update',
                'title' => 'Research marked as done',
                'intro' => 'Analyst <strong>'.$name.'</strong> has marked research as done on case <strong>'.$ref.'</strong>.',
                'message' => $ref.' — Analyst '.$this->completedBy->name.' completed research.'
                    .($this->nextRoleAssigned ? '' : $assignNote),
                'outro' => $this->nextRoleAssigned
                    ? 'QA can now begin work on this case.'
                    : 'Please assign QA to this case so they can start their review.',
                'stage_label' => 'Research done',
            ];
        }

        return [
            'subject' => 'Research completed — start QA',
            'preheader' => 'Analyst '.$this->completedBy->name.' completed research on '.$ref.'.',
            'eyebrow' => 'Ready for QA',
            'title' => 'Research is complete',
            'intro' => 'Analyst <strong>'.$name.'</strong> has completed research on case <strong>'.$ref.'</strong>. You can start QA when ready.',
            'message' => $ref.' — Analyst '.$this->completedBy->name.' completed research. You can start QA.',
            'outro' => 'Sign in to the portal to update the case stage and continue.',
            'stage_label' => 'Research done',
        ];
    }

    /**
     * @return array{subject: string, preheader: string, eyebrow: string, title: string, intro: string, message: string, outro: string, stage_label: string}
     */
    private function qaDoneCopy(bool $isAdmin): array
    {
        $ref = $this->case->reference;
        $name = e($this->completedBy->name);
        $assignNote = ' FQA is not assigned to this case yet — please assign FQA first.';

        if ($isAdmin) {
            return [
                'subject' => 'QA completed review',
                'preheader' => 'QA '.$this->completedBy->name.' marked QA done on '.$ref.'.',
                'eyebrow' => 'Case update',
                'title' => 'QA marked as done',
                'intro' => 'QA <strong>'.$name.'</strong> has marked QA as done on case <strong>'.$ref.'</strong>.',
                'message' => $ref.' — QA '.$this->completedBy->name.' completed their review.'
                    .($this->nextRoleAssigned ? '' : $assignNote),
                'outro' => $this->nextRoleAssigned
                    ? 'FQA can now begin the final review on this case.'
                    : 'Please assign FQA to this case so they can continue.',
                'stage_label' => 'QA done',
            ];
        }

        return [
            'subject' => 'QA completed — start FQA',
            'preheader' => 'QA '.$this->completedBy->name.' completed review on '.$ref.'.',
            'eyebrow' => 'Ready for FQA',
            'title' => 'QA review is complete',
            'intro' => 'QA <strong>'.$name.'</strong> has completed their review on case <strong>'.$ref.'</strong>. You can start FQA when ready.',
            'message' => $ref.' — QA '.$this->completedBy->name.' completed review. You can start FQA.',
            'outro' => 'Sign in to the portal to update the case stage and continue.',
            'stage_label' => 'QA done',
        ];
    }
}
