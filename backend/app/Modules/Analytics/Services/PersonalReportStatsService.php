<?php

namespace App\Modules\Analytics\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Contracts\ReportStatistics;
use App\Modules\Shared\Enums\ReportStatus;

class PersonalReportStatsService
{
    private const IN_PROCESS = [
        ReportStatus::AiValidation,
        ReportStatus::AdminReview,
    ];

    // Once an admin approves a report it counts as approved, also while the government handles it.
    private const APPROVED = [
        ReportStatus::Approved,
        ReportStatus::Forwarded,
        ReportStatus::GovApproved,
        ReportStatus::Scheduled,
    ];

    public function __construct(private readonly ReportStatistics $reports) {}

    /**
     * @return array{total: int, in_process: int, approved: int, rejected: int, cancelled: int, by_status: array<string, int>}
     */
    public function forUser(User $user): array
    {
        $byStatus = $this->reports->countByStatusForReporter($user->getKey());

        return [
            'total' => array_sum($byStatus),
            'in_process' => $this->sum($byStatus, self::IN_PROCESS),
            'approved' => $this->sum($byStatus, self::APPROVED),
            'rejected' => $byStatus[ReportStatus::Rejected->value],
            'cancelled' => $byStatus[ReportStatus::Cancelled->value],
            'by_status' => $byStatus,
        ];
    }

    /**
     * @param  array<string, int>  $byStatus
     * @param  list<ReportStatus>  $statuses
     */
    private function sum(array $byStatus, array $statuses): int
    {
        return array_sum(array_map(fn (ReportStatus $status): int => $byStatus[$status->value], $statuses));
    }
}
