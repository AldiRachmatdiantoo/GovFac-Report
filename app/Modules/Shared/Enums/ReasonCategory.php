<?php

namespace App\Modules\Shared\Enums;

/**
 * Kategori alasan penolakan laporan oleh admin.
 */
enum ReasonCategory: string
{
    case Duplicate = 'duplicate';
    case UnclearEvidence = 'unclear_evidence';
    case NotPublicFacility = 'not_public_facility';
    case Incomplete = 'incomplete';
    case Other = 'other';
}
