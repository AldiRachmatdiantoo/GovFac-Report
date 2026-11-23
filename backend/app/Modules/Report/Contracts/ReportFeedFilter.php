<?php

namespace App\Modules\Report\Contracts;

use App\Modules\Shared\Support\Pagination;

final readonly class ReportFeedFilter
{
    public function __construct(
        public int $perPage = Pagination::DEFAULT_PER_PAGE,
    ) {}
}
