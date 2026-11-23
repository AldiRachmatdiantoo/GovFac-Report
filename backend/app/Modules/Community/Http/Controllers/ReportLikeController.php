<?php

namespace App\Modules\Community\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Community\Services\ReportLikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportLikeController extends Controller
{
    public function __construct(private readonly ReportLikeService $likes) {}

    /**
     * PUT /api/v1/reports/{id}/like
     */
    public function store(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->likes->like($request->user(), $id)]);
    }

    /**
     * DELETE /api/v1/reports/{id}/like
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->likes->unlike($request->user(), $id)]);
    }
}
