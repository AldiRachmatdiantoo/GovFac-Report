<?php

namespace App\Modules\Report\Models;

use App\Modules\Auth\Models\User;
use App\Modules\Shared\Enums\ReasonCategory;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only: baris riwayat tidak pernah diubah atau dihapus.
 */
#[Fillable(['report_id', 'from_status', 'to_status', 'actor_id', 'reason_category', 'note'])]
class ReportStatusHistory extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => ReportStatus::class,
            'to_status' => ReportStatus::class,
            'reason_category' => ReasonCategory::class,
        ];
    }

    /** @return BelongsTo<Report, $this> */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
