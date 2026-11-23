<?php

namespace App\Modules\Analytics\Http\Controllers;

use App\Modules\Analytics\Http\Requests\AdminStatsRequest;
use App\Modules\Analytics\Services\AdminReportStatsService;
use Illuminate\Http\JsonResponse;

class AdminStatsController
{
    public function __construct(private readonly AdminReportStatsService $stats) {}

    /**
     * GET /api/v1/admin/stats
     */
    public function __invoke(AdminStatsRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->stats->summary($request->from(), $request->to())]);
    }
}
