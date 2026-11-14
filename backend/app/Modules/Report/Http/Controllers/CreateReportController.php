<?php

namespace App\Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Report\Http\Requests\StoreReportRequest;
use App\Modules\Report\Http\Resources\ReportResource;
use App\Modules\Report\Services\CreateReportService;
use Illuminate\Http\JsonResponse;

class CreateReportController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreReportRequest $request, CreateReportService $service): JsonResponse
    {
        $report = $service->create($request->user(), $request->validated());

        return (new ReportResource($report))->response()->setStatusCode(201);
    }
}
