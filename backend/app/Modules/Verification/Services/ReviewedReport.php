<?php

namespace App\Modules\Verification\Services;

use App\Modules\AiValidation\Contracts\RecordedAiValidation;
use App\Modules\Report\Models\Report;

/**
 * A report together with the AI result the admin bases the decision on.
 */
final readonly class ReviewedReport
{
    public function __construct(
        public Report $report,
        public ?RecordedAiValidation $aiValidation,
    ) {}
}
