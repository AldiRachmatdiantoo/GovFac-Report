<?php

namespace App\Modules\Notification\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Tells an admin that a report passed AI validation and waits in the queue.
 */
class ReportAwaitingReview extends Notification
{
    public function __construct(
        public readonly int $reportId,
        public readonly ?string $reportCode,
        public readonly string $reportTitle,
        public readonly int $revision,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return 'report_awaiting_review';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $label = $this->reportCode ?? "#{$this->reportId}";
        $resubmitted = $this->revision > 1 ? " (revisi ke-{$this->revision})" : '';

        return [
            'title' => 'Laporan baru menunggu tinjauan',
            'message' => "Laporan {$label} \"{$this->reportTitle}\"{$resubmitted} lolos validasi AI dan masuk antrean admin.",
            'report_id' => $this->reportId,
            'report_code' => $this->reportCode,
            'revision' => $this->revision,
        ];
    }
}
