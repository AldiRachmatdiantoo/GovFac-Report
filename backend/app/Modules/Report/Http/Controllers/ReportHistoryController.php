<?php

namespace App\Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Report\Http\Resources\ReportStatusHistoryResource;
use App\Modules\Report\Services\ReportQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReportHistoryController extends Controller
{
    public function __construct(private readonly ReportQueryService $queries) {}

    /**
     * GET /api/v1/reports/{id}/history
     *
     * Linimasa perubahan status laporan, dari yang paling lama ke paling baru.
     * Akses sama dengan detail laporan.
     */
    public function show(int $id, Request $request)
    {
        $report = $this->queries->findOrFail($id);

        Gate::authorize('view', $report);

        $histories = $this->queries->timeline($report);

        return ReportStatusHistoryResource::collection($histories);
    }
}
