<?php

namespace App\Modules\Report\Repositories;

use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\ReportStatusHistory;
use App\Modules\Shared\Enums\ReasonCategory;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Collection;

/**
 * Riwayat status bersifat append-only: repository ini sengaja hanya
 * menyediakan operasi tambah dan baca, tanpa update maupun delete.
 */
class ReportStatusHistoryRepository
{
    public function append(
        Report $report,
        ?ReportStatus $from,
        ReportStatus $to,
        ?int $actorId = null,
        ?string $note = null,
        ?ReasonCategory $reasonCategory = null,
    ): ReportStatusHistory {
        return ReportStatusHistory::create([
            'report_id' => $report->getKey(),
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actorId,
            'reason_category' => $reasonCategory,
            'note' => $note,
        ]);
    }

    /**
     * Linimasa dari yang paling lama ke yang paling baru.
     *
     * @return Collection<int, ReportStatusHistory>
     */
    public function timeline(Report $report): Collection
    {
        return ReportStatusHistory::query()
            ->with('actor:id,name,role')
            ->where('report_id', $report->getKey())
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }
}
