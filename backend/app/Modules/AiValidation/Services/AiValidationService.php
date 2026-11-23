<?php

namespace App\Modules\AiValidation\Services;

use App\Modules\AiValidation\Contracts\AiValidationResult;
use App\Modules\AiValidation\Contracts\AiValidator;
use App\Modules\AiValidation\Events\ReportValidated;
use App\Modules\AiValidation\Models\AiValidation;
use App\Modules\AiValidation\Repositories\AiValidationRepository;
use App\Modules\Report\Contracts\ReportCriticalityWriter;
use App\Modules\Report\Contracts\ReportStatusService;
use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\ReportStatus;
use App\Modules\Shared\Exceptions\InvalidStatusTransition;
use Illuminate\Support\Facades\DB;

class AiValidationService
{
    public function __construct(
        private readonly AiValidator $validator,
        private readonly AiValidationRepository $validations,
        private readonly ReportStatusService $statuses,
        private readonly ReportCriticalityWriter $criticality,
    ) {}

    public function validate(Report $report, int $revision): void
    {
        if ($this->awaitsValidation($report, $revision)) {
            $this->record($report, $revision, $this->validator->validate($report));
        }
    }

    /**
     * Used once every attempt has failed, so the report never stays stuck in `ai_validation`.
     */
    public function recordUnavailable(Report $report, int $revision): void
    {
        if ($this->awaitsValidation($report, $revision)) {
            $this->record($report, $revision, AiValidationResult::unavailable());
        }
    }

    private function awaitsValidation(Report $report, int $revision): bool
    {
        return $report->status === ReportStatus::AiValidation && $report->revision === $revision;
    }

    private function record(Report $report, int $revision, AiValidationResult $result): void
    {
        try {
            $validation = DB::transaction(function () use ($report, $revision, $result): AiValidation {
                $this->statuses->transition(
                    $report,
                    $result->verdict->nextStatus(),
                    note: $result->note(),
                    reasonCategory: $result->reasonCategory,
                );

                if ($result->criticality !== null) {
                    $this->criticality->assign($report, $result->criticality);
                }

                return $this->validations->create($report, $revision, $result);
            });
        } catch (InvalidStatusTransition) {
            // The reporter cancelled the report while we were validating it.
            return;
        }

        ReportValidated::dispatch($report, $validation);
    }
}
