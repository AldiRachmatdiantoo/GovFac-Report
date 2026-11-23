<?php

namespace App\Modules\AiValidation\Contracts;

/**
 * Read access to AI validation results for modules such as Verification.
 */
interface AiValidationReader
{
    /**
     * The result for the report's newest revision, or null when the AI has not finished yet.
     */
    public function latestForReport(int $reportId): ?RecordedAiValidation;
}
