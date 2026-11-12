<?php

namespace App\Modules\Report\Policies;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\ReportStatus;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Auth\Access\Response;

/**
 * Penolakan dijawab 404, bukan 403, agar keberadaan laporan tidak bocor
 * kepada pihak yang tidak berhak.
 */
class ReportPolicy
{
    /**
     * Status yang sudah boleh dilihat pemerintah (forwarded dan sesudahnya).
     */
    private const GOVERNMENT_VISIBLE = [
        ReportStatus::Forwarded,
        ReportStatus::GovApproved,
        ReportStatus::Scheduled,
    ];

    public function view(User $user, Report $report): Response
    {
        if ($user->getKey() === $report->reporter_id) {
            return Response::allow();
        }

        $allowed = match ($user->role) {
            UserRole::Admin => true,
            UserRole::Government => in_array($report->status, self::GOVERNMENT_VISIBLE, true),
            // User lain belum boleh melihat laporan orang lain; feed publik baru ada di PBI-11.
            default => false,
        };

        return $allowed ? Response::allow() : Response::denyAsNotFound();
    }
}
