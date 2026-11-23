<?php

namespace App\Modules\AiValidation\Services;

use App\Modules\AiValidation\Contracts\AiValidationReader;
use App\Modules\AiValidation\Contracts\RecordedAiValidation;
use App\Modules\AiValidation\Repositories\AiValidationRepository;

class EloquentAiValidationReader implements AiValidationReader
{
    public function __construct(private readonly AiValidationRepository $validations) {}

    public function latestForReport(int $reportId): ?RecordedAiValidation
    {
        $validation = $this->validations->latestForReport($reportId);

        if ($validation === null) {
            return null;
        }

        return new RecordedAiValidation(
            revision: $validation->revision,
            verdict: $validation->verdict,
            score: $validation->score,
            criticality: $validation->criticality,
            reasons: array_values($validation->reasons ?? []),
            model: $validation->model,
            validatedAt: $validation->created_at,
        );
    }
}
