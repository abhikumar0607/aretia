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
        $this->report->loadMissing('caseFile');

        return (new MailMessage)
            ->subject('Aretia — Report Ready')
            ->line('Your due diligence report is ready for download.')
            ->action('Download Report', route('client.reports.show', $this->report))
            ->line('Case reference: '.$this->report->caseFile->reference);
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
