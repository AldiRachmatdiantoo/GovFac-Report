<?php

namespace App\Modules\AiValidation\Listeners;

use App\Modules\AiValidation\Jobs\ValidateReport;
use App\Modules\Report\Events\ReportSubmitted;

class QueueReportValidation
{
    public function handle(ReportSubmitted $event): void
    {
        ValidateReport::dispatch($event->report, $event->report->revision);
    }
}
