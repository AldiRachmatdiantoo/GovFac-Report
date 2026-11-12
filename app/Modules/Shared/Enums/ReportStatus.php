<?php

namespace App\Modules\Shared\Enums;

enum ReportStatus: string
{
    case AiValidation = 'ai_validation';
    case AdminReview = 'admin_review';
    case Rejected = 'rejected';
    case Approved = 'approved';
    case Forwarded = 'forwarded';
    case GovApproved = 'gov_approved';
    case Scheduled = 'scheduled';
    case Cancelled = 'cancelled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
