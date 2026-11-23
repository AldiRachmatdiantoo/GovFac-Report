<?php

namespace App\Modules\Report\Repositories;

use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\Criticality;

class ReportCriticalityRepository
{
    public function assign(Report $report, Criticality $criticality): void
    {
        $report->criticality = $criticality;
        $report->save();
    }
}
