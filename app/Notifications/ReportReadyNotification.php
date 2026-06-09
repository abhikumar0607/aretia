<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportReadyNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(public Report $report) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->report->loadMissing(['caseFile.order.package']);

        $case = $this->report->caseFile;
        $isProtected = $this->report->is_password_protected && filled($this->report->file_password);
        $highlights = [
            'Case reference' => e($case->reference),
        ];

        if ($case?->order?->package?->name) {
            $highlights['Package'] = e($case->order->package->name);
        }

        if ($isProtected) {
            $highlights['File password'] = e($this->report->file_password);
        }

        $intro = 'Our analyst team has completed your due diligence case. Your report is now available to download from the portal.';
        $outro = 'For security, the report is delivered through the encrypted portal. Please log in to download it.';

        if ($isProtected) {
            $intro .= ' This report is password protected — use the file password above when downloading.';
            $outro = 'Enter the file password on the download page to access your report. Keep this password confidential.';
        }

        return (new MailMessage)
            ->subject($isProtected ? 'Aretia — Report Ready (password included)' : 'Aretia — Report Ready')
            ->view('emails.notification', [
                'subject' => 'Your due diligence report is ready',
                'preheader' => $isProtected
                    ? 'Report for case '.$case->reference.' is ready. Your file password is included in this email.'
                    : 'Report for case '.$case->reference.' is available to download.',
                'eyebrow' => 'Report Ready',
                'accent' => 'success',
                'title' => 'Your report is ready',
                'greeting' => 'Hello '.$notifiable->name.',',
                'intro' => $intro,
                'highlights' => $highlights,
                'cta_url' => route('client.reports.show', $this->report),
                'cta_label' => 'Download report',
                'outro' => $outro,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $this->report->loadMissing('caseFile');

        $message = 'Report for case '.$this->report->caseFile->reference.' is available.';
        if ($this->report->is_password_protected && filled($this->report->file_password)) {
            $message .= ' File password: '.$this->report->file_password;
        }

        return [
            'title' => 'Report ready',
            'message' => $message,
            'url' => route('client.reports.show', $this->report),
            'type' => 'report_ready',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastType(): string
    {
        return 'report.ready';
    }
}
