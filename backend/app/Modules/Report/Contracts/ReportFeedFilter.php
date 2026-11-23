<?php

namespace App\Modules\Report\Contracts;

use App\Modules\Shared\Enums\Criticality;
use App\Modules\Shared\Enums\ReportStatus;
use App\Modules\Shared\Support\Pagination;

/**
 * Every filter is optional and they combine with AND.
 */
final readonly class ReportFeedFilter
{
    public const SORT_NEWEST = 'newest';

    public const SORT_LIKES = 'likes';

    public const SORTS = [self::SORT_NEWEST, self::SORT_LIKES];

    /**
     * @param  string|null  $search  matched against code, title and description, case-insensitive
     * @param  string|null  $tag  tag name, lower case and without the leading `#`
     */
    public function __construct(
        public ?string $search = null,
        public ?ReportStatus $status = null,
        public ?Criticality $criticality = null,
        public ?string $tag = null,
        public string $sort = self::SORT_NEWEST,
        public int $perPage = Pagination::DEFAULT_PER_PAGE,
    ) {}
}
