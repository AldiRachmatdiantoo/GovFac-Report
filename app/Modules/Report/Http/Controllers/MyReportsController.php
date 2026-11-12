<?php

namespace App\Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Report\Http\Requests\MyReportsRequest;
use App\Modules\Report\Http\Resources\ReportResource;
use App\Modules\Report\Services\ReportQueryService;
use Illuminate\Http\Request;

class MyReportsController extends Controller
{
    public function __construct(private readonly ReportQueryService $queries) {}

    /**
     * GET /api/v1/reports/mine
     *
     * Laporan milik user yang sedang login, berhalaman, terbaru di atas,
     * filter `status` opsional.
     */
    public function index(MyReportsRequest $request)
    {
        $paginated = $this->queries->paginateMine(
            user: $request->user(),
            status: $request->status(),
            perPage: $request->perPage(),
        );

        return ReportResource::collection($paginated);
    }
}
