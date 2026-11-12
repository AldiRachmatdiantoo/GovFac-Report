<?php

namespace App\Modules\Report\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Report\Models\Report;
use App\Modules\Report\Models\ReportStatusHistory;
use App\Modules\Report\Repositories\ReportReadRepository;
use App\Modules\Report\Repositories\ReportStatusHistoryRepository;
use App\Modules\Shared\Enums\ReportStatus;
use App\Modules\Shared\Support\Pagination;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReportQueryService
{
    public function __construct(
        private readonly ReportReadRepository $reports,
        private readonly ReportStatusHistoryRepository $histories,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Report>
     */
    public function paginateMine(User $user, ?ReportStatus $status = null, int $perPage = Pagination::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        return $this->reports->paginateByReporter($user->getKey(), $status, $perPage);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function findDetailOrFail(int $id): Report
    {
        return $this->reports->findDetailById($id)
            ?? throw (new ModelNotFoundException)->setModel(Report::class, [$id]);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $id): Report
    {
        return $this->reports->findById($id)
            ?? throw (new ModelNotFoundException)->setModel(Report::class, [$id]);
    }

    /**
     * @return Collection<int, ReportStatusHistory>
     */
    public function timeline(Report $report): Collection
    {
        return $this->histories->timeline($report);
    }
}
