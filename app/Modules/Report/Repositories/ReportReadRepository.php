<?php

namespace App\Modules\Report\Repositories;

use App\Modules\Report\Models\Report;
use App\Modules\Shared\Enums\ReportStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Query baca laporan milik Dev D. Query tulis ada di ReportRepository (Dev C).
 */
class ReportReadRepository
{
    /**
     * Ambil baris laporan dengan kunci tulis (SELECT ... FOR UPDATE).
     * Harus dipanggil di dalam transaksi.
     */
    public function lockById(int $id): Report
    {
        return Report::query()->lockForUpdate()->findOrFail($id);
    }

    public function findById(int $id): ?Report
    {
        return Report::query()->find($id);
    }

    public function findDetailById(int $id): ?Report
    {
        return Report::query()
            ->with(['photos', 'tags', 'reporter:id,name'])
            ->find($id);
    }

    /**
     * @return LengthAwarePaginator<int, Report>
     */
    public function paginateByReporter(int $reporterId, ?ReportStatus $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return Report::query()
            ->with(['photos', 'tags'])
            ->where('reporter_id', $reporterId)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
