<?php

namespace App\Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Report\Http\Requests\UpdateReportRequest;
use App\Modules\Report\Http\Resources\ReportResource;
use App\Modules\Report\Services\ReportQueryService;
use App\Modules\Report\Services\UpdateReportService;
use Illuminate\Support\Facades\Gate;

class UpdateReportController extends Controller
{
    public function __construct(
        private readonly ReportQueryService $queries,
        private readonly UpdateReportService $updates,
    ) {}

    public function __invoke(UpdateReportRequest $request, int $id): ReportResource
    {
        $report = $this->queries->findOrFail($id);

        Gate::authorize('update', $report);

        return new ReportResource($this->updates->update($request->user(), $report, $request->validated()));
    }
}
