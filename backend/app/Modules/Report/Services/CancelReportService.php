<?php

namespace App\Modules\Report\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Contracts\ReportStatusService;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Repositories\ReportRepository;
use App\Modules\Shared\Enums\ReportStatus;
use App\Modules\Shared\Exceptions\InvalidStatusTransition;

class CancelReportService
{
    public function __construct(
        private readonly ReportStatusService $statuses,
        private readonly ReportRepository $reports,
    ) {}

    /**
     * The transition map decides which statuses may be cancelled; once the government
     * has approved a report it can no longer be cancelled.
     *
     * @throws InvalidStatusTransition
     */
    public function cancel(User $actor, Report $report, ?string $reason): Report
    {
        try {
            $this->statuses->transition($report, ReportStatus::Cancelled, $actor, $reason);
        } catch (InvalidStatusTransition $exception) {
            throw new InvalidStatusTransition(
                $exception->current,
                ReportStatus::Cancelled,
                sprintf('Laporan berstatus %s tidak bisa dibatalkan.', $exception->current->value),
            );
        }

        return $this->reports->loadRelations($report);
    }
}
