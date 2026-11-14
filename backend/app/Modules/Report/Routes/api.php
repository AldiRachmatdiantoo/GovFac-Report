<?php

use App\Modules\Report\Http\Controllers\CreateReportController;
use App\Modules\Report\Http\Controllers\MyReportsController;
use App\Modules\Report\Http\Controllers\ReportHistoryController;
use App\Modules\Report\Http\Controllers\ShowReportController;
use App\Modules\Report\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

/*
| Routes modul Report.
| Sudah otomatis berada di bawah prefix /api/v1 dengan middleware "api".
*/

// PBI-5
Route::middleware('auth:api')->group(function (): void {
    Route::post('reports', CreateReportController::class)->middleware('role:user')->name('reports.store');
    Route::get('tags', TagController::class)->name('tags.index');
});

// PBI-7: Pelacakan Status Laporan (Dev D)
Route::middleware('auth:api')->group(function () {
    Route::get('/reports/mine', [MyReportsController::class, 'index']);
    Route::get('/reports/{id}/history', [ReportHistoryController::class, 'show'])->whereNumber('id');
    Route::get('/reports/{id}', [ShowReportController::class, 'show'])->whereNumber('id');
});
