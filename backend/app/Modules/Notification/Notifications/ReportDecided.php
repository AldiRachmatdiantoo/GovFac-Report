<?php

namespace App\Modules\Notification\Notifications;

use App\Modules\Shared\Enums\ReasonCategory;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Notifications\Notification;

/**
 * Tells the reporter that their report was approved or rejected.
 */
class ReportDecided extends Notification
{
    public function __construct(
        public readonly int $reportId,
        public readonly ?string $reportCode,
        public readonly string $reportTitle,
        public readonly ReportStatus $decision,
        public readonly bool $decidedBySystem,
        public readonly ?ReasonCategory $reasonCategory,
        public readonly ?string $note,
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
        return $this->decision === ReportStatus::Approved ? 'report_approved' : 'report_rejected';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $label = $this->reportCode ?? "#{$this->reportId}";

        if ($this->decision === ReportStatus::Approved) {
            $title = 'Laporan disetujui';
            $message = "Laporan {$label} \"{$this->reportTitle}\" disetujui admin dan akan diteruskan ke pemerintah.";
        } else {
            $by = $this->decidedBySystem ? 'validasi AI' : 'admin';
            $title = 'Laporan ditolak';
            $message = "Laporan {$label} \"{$this->reportTitle}\" ditolak oleh {$by}. Baca catatannya, perbaiki, lalu kirim ulang.";
        }

        return [
            'title' => $title,
            'message' => $message,
            'report_id' => $this->reportId,
            'report_code' => $this->reportCode,
            'status' => $this->decision->value,
            'reason_category' => $this->reasonCategory?->value,
            'note' => $this->note,
        ];
    }
}
