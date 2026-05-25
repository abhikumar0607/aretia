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
        $highlights = [
            'Case reference' => e($case->reference),
        ];

        if ($case?->order?->package?->name) {
            $highlights['Package'] = e($case->order->package->name);
        }

        return (new MailMessage)
            ->subject('Aretia — Report Ready')
            ->view('emails.notification', [
                'subject' => 'Your due diligence report is ready',
                'preheader' => 'Report for case '.$case->reference.' is available to download.',
                'eyebrow' => 'Report Ready',
                'accent' => 'success',
                'title' => 'Your report is ready',
                'greeting' => 'Hello '.$notifiable->name.',',
                'intro' => 'Our analyst team has completed your due diligence case. Your report is now available to download from the portal.',
                'highlights' => $highlights,
                'cta_url' => route('client.reports.show', $this->report),
                'cta_label' => 'Download report',
                'outro' => 'For security, the report is delivered through the encrypted portal. Please log in to download it.',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $this->report->loadMissing('caseFile');

        return [
            'title' => 'Report ready',
            'message' => 'Report for case '.$this->report->caseFile->reference.' is available.',
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
