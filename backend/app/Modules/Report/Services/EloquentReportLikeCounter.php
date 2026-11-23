<?php

namespace App\Modules\Report\Services;

use App\Modules\Report\Contracts\ReportLikeCounter;
use App\Modules\Report\Repositories\ReportLikeCountRepository;

class EloquentReportLikeCounter implements ReportLikeCounter
{
    public function __construct(private readonly ReportLikeCountRepository $counts) {}

    public function increment(int $reportId): int
    {
        $this->counts->increment($reportId);

        return $this->counts->count($reportId);
    }

    public function decrement(int $reportId): int
    {
        $this->counts->decrement($reportId);

        return $this->counts->count($reportId);
    }

    public function count(int $reportId): int
    {
        return $this->counts->count($reportId);
    }
}
