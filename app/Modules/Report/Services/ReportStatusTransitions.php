<?php

namespace App\Modules\Report\Services;

use App\Modules\Shared\Enums\ReportStatus;

/**
 * Mendefinisikan matriks transisi status yang sah untuk laporan.
 *
 * Kelas ini hanya memeriksa keabsahan transisi STATUS. Siapa yang boleh
 * memicu transisi (peran, kepemilikan) diperiksa oleh Policy modul pemanggil.
 *
 * Tabel transisi valid (dari PRD):
 *
 *   Dari                Ke
 *   ai_validation       -> admin_review, rejected, cancelled
 *   admin_review        -> approved, rejected, cancelled
 *   rejected            -> ai_validation (untuk edit ulang)
 *   approved            -> forwarded, cancelled
 *   forwarded           -> gov_approved, cancelled
 *   gov_approved        -> scheduled
 *   scheduled, cancelled -> (akhir, tidak boleh transisi lagi)
 */
class ReportStatusTransitions
{
    /**
     * Memeriksa apakah transisi dari $from ke $to diperbolehkan.
     */
    public static function canTransition(ReportStatus $from, ReportStatus $to): bool
    {
        return match ($from) {
            ReportStatus::AiValidation => in_array($to, [
                ReportStatus::AdminReview,
                ReportStatus::Rejected,
                ReportStatus::Cancelled,
            ], true),

            ReportStatus::AdminReview => in_array($to, [
                ReportStatus::Approved,
                ReportStatus::Rejected,
                ReportStatus::Cancelled,
            ], true),

            ReportStatus::Rejected => in_array($to, [
                ReportStatus::AiValidation,
            ], true),

            ReportStatus::Approved => in_array($to, [
                ReportStatus::Forwarded,
                ReportStatus::Cancelled,
            ], true),

            ReportStatus::Forwarded => in_array($to, [
                ReportStatus::GovApproved,
                ReportStatus::Cancelled,
            ], true),

            ReportStatus::GovApproved => in_array($to, [
                ReportStatus::Scheduled,
            ], true),

            // Akhir: tidak boleh transisi lagi
            ReportStatus::Scheduled,
            ReportStatus::Cancelled => false,
        };
    }

    /**
     * Mengembalikan daftar status yang bisa menjadi target dari status $from.
     *
     * @return list<ReportStatus>
     */
    public static function nextStates(ReportStatus $from): array
    {
        return match ($from) {
            ReportStatus::AiValidation => [
                ReportStatus::AdminReview,
                ReportStatus::Rejected,
                ReportStatus::Cancelled,
            ],

            ReportStatus::AdminReview => [
                ReportStatus::Approved,
                ReportStatus::Rejected,
                ReportStatus::Cancelled,
            ],

            ReportStatus::Rejected => [
                ReportStatus::AiValidation,
            ],

            ReportStatus::Approved => [
                ReportStatus::Forwarded,
                ReportStatus::Cancelled,
            ],

            ReportStatus::Forwarded => [
                ReportStatus::GovApproved,
                ReportStatus::Cancelled,
            ],

            ReportStatus::GovApproved => [
                ReportStatus::Scheduled,
            ],

            ReportStatus::Scheduled,
            ReportStatus::Cancelled => [],
        };
    }
}
