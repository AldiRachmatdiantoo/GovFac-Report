<?php

namespace App\Modules\Report\Contracts;

use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\Criticality;

/**
 * Lets the AiValidation module copy its criticality onto the report.
 */
interface ReportCriticalityWriter
{
    public function assign(Report $report, Criticality $criticality): void;
}
