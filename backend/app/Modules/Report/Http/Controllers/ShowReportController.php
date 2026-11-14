<?php

namespace App\Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Report\Http\Resources\ReportResource;
use App\Modules\Report\Services\ReportQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ShowReportController extends Controller
{
    public function __construct(private readonly ReportQueryService $queries) {}

    /**
     * GET /api/v1/reports/{id}
     *
     * Detail lengkap laporan. Akses disesuaikan lewat Policy: pemilik, admin,
     * dan pemerintah (hanya status forwarded ke atas) boleh lihat.
     * Selain itu, 404.
     */
    public function show(int $id, Request $request)
    {
        $report = $this->queries->findDetailOrFail($id);

        Gate::authorize('view', $report);

        return new ReportResource($report);
    }
}
