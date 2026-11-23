<?php

namespace App\Modules\Analytics\Http\Controllers;

use App\Modules\Analytics\Services\PersonalReportStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyReportStatsController
{
    public function __construct(private readonly PersonalReportStatsService $stats) {}

    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->stats->forUser($request->user())]);
    }
}
