<?php

namespace App\Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Report\Http\Requests\CancelReportRequest;
use App\Modules\Report\Http\Resources\ReportResource;
use App\Modules\Report\Services\CancelReportService;
use App\Modules\Report\Services\ReportQueryService;
use Illuminate\Support\Facades\Gate;

class CancelReportController extends Controller
{
    public function __construct(
        private readonly ReportQueryService $queries,
        private readonly CancelReportService $cancellations,
    ) {}

    public function __invoke(CancelReportRequest $request, int $id): ReportResource
    {
        $report = $this->queries->findOrFail($id);

        Gate::authorize('cancel', $report);

        return new ReportResource($this->cancellations->cancel($request->user(), $report, $request->reason()));
    }
}
