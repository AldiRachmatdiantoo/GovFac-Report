<?php

namespace App\Modules\Report\Contracts;

/**
 * Lets the Community module keep `reports.likes_count` in step with its `report_likes` rows.
 */
interface ReportLikeCounter
{
    /**
     * @return int the new like count
     */
    public function increment(int $reportId): int;

    /**
     * Never goes below zero.
     *
     * @return int the new like count
     */
    public function decrement(int $reportId): int;

    public function count(int $reportId): int;
}
