<?php

namespace App\Modules\Report\Repositories;

use App\Modules\Report\Models\Report;

class ReportReviewRepository
{
    public function findDetail(int $reportId): ?Report
    {
        return Report::query()
            ->with(['photos', 'tags', 'reporter:id,name'])
            ->find($reportId);
    }
}
