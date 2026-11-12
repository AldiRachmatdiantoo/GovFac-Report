<?php

use App\Modules\Report\Http\Controllers\MyReportsController;
use App\Modules\Report\Http\Controllers\ReportHistoryController;
use App\Modules\Report\Http\Controllers\ShowReportController;
use Illuminate\Support\Facades\Route;

/*
| Routes modul Report.
| Sudah otomatis berada di bawah prefix /api/v1 dengan middleware "api".
*/

// PBI-7: Pelacakan Status Laporan (Dev D)
Route::middleware('auth:api')->group(function () {
    Route::get('/reports/mine', [MyReportsController::class, 'index']);
    Route::get('/reports/{id}/history', [ReportHistoryController::class, 'show'])->whereNumber('id');
    Route::get('/reports/{id}', [ShowReportController::class, 'show'])->whereNumber('id');
});

