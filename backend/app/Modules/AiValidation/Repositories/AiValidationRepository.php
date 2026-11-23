<?php

namespace App\Modules\AiValidation\Repositories;

use App\Modules\AiValidation\Contracts\AiValidationResult;
use App\Modules\AiValidation\Models\AiValidation;
use App\Modules\Report\Models\Report;

class AiValidationRepository
{
    public function create(Report $report, int $revision, AiValidationResult $result): AiValidation
    {
        return AiValidation::query()->create([
            'report_id' => $report->getKey(),
            'revision' => $revision,
            'verdict' => $result->verdict,
            'score' => $result->score,
            'criticality' => $result->criticality,
            'reasons' => $result->reasons,
            'model' => $result->model,
        ]);
    }

    public function latestForReport(int $reportId): ?AiValidation
    {
        return AiValidation::query()
            ->where('report_id', $reportId)
            ->orderByDesc('revision')
            ->first();
    }
}
