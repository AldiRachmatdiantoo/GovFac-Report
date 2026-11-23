<?php

namespace App\Modules\Report\Contracts;

use App\Modules\Shared\Enums\ReportStatus;
use App\Modules\Shared\Support\Pagination;
use Carbon\CarbonInterface;

final readonly class ReviewDecisionFilter
{
    /**
     * @param  ReportStatus|null  $decision  Approved or Rejected; null for both
     * @param  CarbonInterface|null  $from  inclusive lower bound of the decision time
     * @param  CarbonInterface|null  $to  inclusive upper bound of the decision time
     */
    public function __construct(
        public ?ReportStatus $decision = null,
        public ?CarbonInterface $from = null,
        public ?CarbonInterface $to = null,
        public int $perPage = Pagination::DEFAULT_PER_PAGE,
    ) {}
}
