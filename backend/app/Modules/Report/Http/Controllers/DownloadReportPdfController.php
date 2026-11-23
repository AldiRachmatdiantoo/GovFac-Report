<?php

namespace App\Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Report\Services\ReportPdfService;
use App\Modules\Report\Services\ReportQueryService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class DownloadReportPdfController extends Controller
{
    public function __construct(
        private readonly ReportQueryService $queries,
        private readonly ReportPdfService $pdfs,
    ) {}

    public function __invoke(int $id): Response
    {
        $report = $this->queries->findDetailOrFail($id);

        Gate::authorize('downloadPdf', $report);

        return response($this->pdfs->render($report), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$report->code.'.pdf"',
        ]);
    }
}
