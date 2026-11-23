<?php

namespace App\Modules\Verification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Verification\Http\Requests\AdminHistoryRequest;
use App\Modules\Verification\Http\Requests\AdminQueueRequest;
use App\Modules\Verification\Http\Resources\AdminDecisionResource;
use App\Modules\Verification\Http\Resources\AdminQueueItemResource;
use App\Modules\Verification\Services\ReportReviewService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminReviewListController extends Controller
{
    public function __construct(private readonly ReportReviewService $reviews) {}

    /**
     * GET /api/v1/admin/reports/queue
     */
    public function queue(AdminQueueRequest $request): AnonymousResourceCollection
    {
        return AdminQueueItemResource::collection($this->reviews->queue($request->perPage()));
    }

    /**
     * GET /api/v1/admin/reports/history
     */
    public function history(AdminHistoryRequest $request): AnonymousResourceCollection
    {
        return AdminDecisionResource::collection($this->reviews->history($request->filter()));
    }
}
