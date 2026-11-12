<?php

namespace App\Modules\Report\Services;

use DateTimeInterface;
use InvalidArgumentException;

class ReportCodeGenerator
{
    public function generate(int $reportId, DateTimeInterface $createdAt): string
    {
        if ($reportId < 1 || $reportId > 999999) {
            throw new InvalidArgumentException('Report must have a persisted ID between 1 and 999999.');
        }

        return sprintf('GFR-%s-%06d', $createdAt->format('Y'), $reportId);
    }
}
