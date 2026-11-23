<?php

namespace App\Modules\Verification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Verification\Http\Requests\ApproveReportRequest;
use App\Modules\Verification\Http\Requests\RejectReportRequest;
use App\Modules\Verification\Http\Resources\AdminReportResource;
use App\Modules\Verification\Services\ReportReviewService;

class AdminReportController extends Controller
{
    public function __construct(private readonly ReportReviewService $reviews) {}

    /**
     * GET /api/v1/admin/reports/{id}
     */
    public function show(int $id): AdminReportResource
    {
        return new AdminReportResource($this->reviews->detail($id));
    }

    /**
     * POST /api/v1/admin/reports/{id}/approve
     */
    public function approve(ApproveReportRequest $request, int $id): AdminReportResource
    {
        return new AdminReportResource($this->reviews->approve($request->user(), $id, $request->note()));
    }

    /**
     * POST /api/v1/admin/reports/{id}/reject
     */
    public function reject(RejectReportRequest $request, int $id): AdminReportResource
    {
        return new AdminReportResource(
            $this->reviews->reject($request->user(), $id, $request->reasonCategory(), $request->note()),
        );
    }
}
