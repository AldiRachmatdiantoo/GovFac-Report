<?php

namespace App\Modules\Report\Services;

use App\Modules\Report\Contracts\ReportCriticalityWriter;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Repositories\ReportCriticalityRepository;
use App\Modules\Shared\Enums\Criticality;

class EloquentReportCriticalityWriter implements ReportCriticalityWriter
{
    public function __construct(private readonly ReportCriticalityRepository $reports) {}

    public function assign(Report $report, Criticality $criticality): void
    {
        $this->reports->assign($report, $criticality);
    }
}
