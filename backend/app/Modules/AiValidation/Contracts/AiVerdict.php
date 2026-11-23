<?php

namespace App\Modules\AiValidation\Contracts;

use App\Modules\Shared\Enums\ReportStatus;

enum AiVerdict: string
{
    case Pass = 'pass';
    case Flag = 'flag';
    case Fail = 'fail';

    public function nextStatus(): ReportStatus
    {
        return $this === self::Fail ? ReportStatus::Rejected : ReportStatus::AdminReview;
    }
}
