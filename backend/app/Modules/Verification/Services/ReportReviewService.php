<?php

namespace App\Modules\Verification\Services;

use App\Modules\AiValidation\Contracts\AiValidationReader;
use App\Modules\Auth\Models\User;
use App\Modules\Report\Contracts\ReportReview;
use App\Modules\Report\Contracts\ReportStatusService;
use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\ReasonCategory;
use App\Modules\Shared\Enums\ReportStatus;
use App\Modules\Shared\Exceptions\InvalidStatusTransition;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReportReviewService
{
    public function __construct(
        private readonly ReportReview $reports,
        private readonly ReportStatusService $statuses,
        private readonly AiValidationReader $aiValidations,
    ) {}

    /**
     * @throws ModelNotFoundException
     */
    public function detail(int $reportId): ReviewedReport
    {
        $report = $this->findOrFail($reportId);

        return new ReviewedReport($report, $this->aiValidations->latestForReport($reportId));
    }

    /**
     * @throws ModelNotFoundException
     * @throws InvalidStatusTransition when the report is not waiting for an admin decision
     */
    public function approve(User $admin, int $reportId, ?string $note): ReviewedReport
    {
        return $this->decide($admin, $reportId, ReportStatus::Approved, $note);
    }

    /**
     * @throws ModelNotFoundException
     * @throws InvalidStatusTransition when the report is not waiting for an admin decision
     */
    public function reject(User $admin, int $reportId, ReasonCategory $reasonCategory, string $note): ReviewedReport
    {
        return $this->decide($admin, $reportId, ReportStatus::Rejected, $note, $reasonCategory);
    }

    private function decide(User $admin, int $reportId, ReportStatus $decision, ?string $note, ?ReasonCategory $reasonCategory = null): ReviewedReport
    {
        $report = $this->findOrFail($reportId);

        // The transition map also allows ai_validation -> rejected (an AI verdict), so we
        // check here that an admin only ever decides on reports in admin_review.
        if ($report->status !== ReportStatus::AdminReview) {
            throw new InvalidStatusTransition(
                $report->status,
                $decision,
                sprintf('Keputusan hanya bisa diambil pada laporan berstatus admin_review, bukan %s.', $report->status->value),
            );
        }

        $this->statuses->transition($report, $decision, $admin, $note, $reasonCategory);

        return $this->detail($reportId);
    }

    private function findOrFail(int $reportId): Report
    {
        return $this->reports->findDetail($reportId)
            ?? throw (new ModelNotFoundException)->setModel(Report::class, [$reportId]);
    }
}
