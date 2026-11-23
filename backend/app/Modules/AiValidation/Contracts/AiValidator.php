<?php

namespace App\Modules\AiValidation\Contracts;

use App\Modules\Report\Models\Report;

/**
 * We change AI providers by swapping this binding in AiValidationServiceProvider.
 */
interface AiValidator
{
    /**
     * Throwing counts as a failed attempt; the job retries and finally falls back to `flag`.
     */
    public function validate(Report $report): AiValidationResult;
}
